<?php


/**
 * Description of GitShell
 *
 * @author Chris
 */
class GitShell extends AppShell {

	public  $uses = array('SshKey', 'Project', 'Collaborator', 'Setting');

	// Git read and write commands, for checking access permissions
	private $read_commands  = array('git-upload-pack',  'git upload-pack');
	private $write_commands = array('git-receive-pack', 'git receive-pack');

	/*public function __construct($stdout = null, $stderr = null, $stdin = null) {
		Configure::write('Cache.disable', true);
		parent::__construct($stdout, $stderr, $stdin);
	}*/

	public function main() {
		$this->out("You need to specify a command. Try 'sync_keys' or 'serve'.");
	}

	// Prints out an authorized_keys file to STDOUT for all users' keys
	public function authorized_keys() {

		$sourcekettleConfig = $this->getSourceKettleConfig();
		$requestedUser = $this->args[0];

		// Not the git repo user, bomb out
		if ($requestedUser !== $sourcekettleConfig['SourceRepository']['user']['value']) {
			exit(0);
		}

		// We will auto-run out git serve command when the git user logs in, and we should disable any
		// features that may be used for nefarious purposes
		$template = 'command="%s %s",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty %s'."\n";

		// This is the git-serve command that will be run when git logs in
		// CAKE/Console/cake is the cakephp console command, APP is our application dir, and git serve is the sourcekettle console command
		$cmd = CAKE . 'Console' . DS . 'cake -app \'' . APP . '\'' . ' git serve';

		// Now list all SSH keys
		foreach ($this->SshKey->find('all') as $key) {
			$sshkey = $key['SshKey']['key'];
			$userid = $key['User']['id'];

			// Sanity check the user ID
			if (!isset($userid) || $userid <= 0) {
				continue;
			}

			// Sanity check the key
			if (strlen($sshkey) <= 40) {
				continue;
			}

			// Remove newlines if they've added any
			$content = trim(str_replace(array("\n", "\r"), '', $sshkey));
		
			// ...and print the key + command
			printf($template, $cmd, $userid, $content);
		}
	}

	/**
	 * Syncs all of the SSH keys to the git user's authorized_keys file to allow for ssh access
	 */
	public function sync_keys() {

		$sourcekettle_config = $this->getSourceKettleConfig();

		// Don't bother unless a key's actually been changed...
		if ($sourcekettle_config['Status']['sync_required']['value'] != 1) {
			exit(0);
		}


		// Get username from config, and get info from the passwd file (or other entry)
		$git_user	= $sourcekettle_config['SourceRepository']['user']['value'];
		$git_details = posix_getpwnam($git_user);

		// Sanity check #1, fail if the user doesn't exist...
		if(!$git_details){
			$this->err(__("Cannot sync keys - git user '$git_user' does not exist - have you set up SourceKettle properly?"));
			exit(1);
		}

		// Get their homedir
		$git_homedir = $git_details['dir'];

		// Sanity check #2, make sure they have a .ssh directory - we *could* auto-create this, but I'd rather fail safe
		if(!is_dir($git_homedir.'/.ssh')){
			$this->err(__("Cannot sync keys - $git_homedir/.ssh not found - have you set up SourceKettle properly?"));
			exit(1);
		}

		// Now we know where to write to...
		$ssh_keyfile = $git_homedir.'/.ssh/authorized_keys';

		// Get all of the SSH keys from the database
		$keys = $this->SshKey->find('all');
		$prepared_keys = array();

		// We will auto-run out git serve command when the git user logs in, and we should disable any
		// features that may be used for nefarious purposes
		$template = 'command="%s %s",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty %s';

		// This is the git-serve command that will be run when git logs in
		// CAKE/Console/cake is the cakephp console command, APP is our application dir, and git serve is the sourcekettle console command
		$cmd = CAKE . 'Console' . DS . 'cake -app \'' . APP . '\'' . ' git serve';

		// Build up a list of SSH keys to write to file
		// NOTE - very small risk of memory exhaustion, it'd take a huge number of keys though...
		$out = '';
		foreach ($keys as $key) {
			$sshkey = $key['SshKey']['key'];
			$userid = $key['User']['id'];
			if (!isset($userid) || $userid <= 0) {
				$this->out("Bad key detected! ($sshkey has no userid $userid)");
				continue;
			}

			// Sanity check the key
			if (strlen($sshkey) > 40) {
				$content = trim(str_replace(array("\n", "\r"), '', $sshkey));
				$out .= sprintf($template, $cmd, $userid, $content) . "\n";
			}
		}

		// Write to the file, making sure we get an exclusive lock to prevent corruption
		file_put_contents($ssh_keyfile, $out, LOCK_EX);

		// Make sure it's only readable/writable by the git user
		chmod($ssh_keyfile, 0600);

		// Don't sync again unless keys have changed
		$this->Setting->syncRequired(false);
	}

	// Helper functions to validate git commands
	private function isReadCommand($command){
		return in_array($command, $this->read_commands);
	}
	private function isWriteCommand($command){
		return in_array($command, $this->write_commands);
	}
	private function isValidGitCommand($command){
		return ($this->isReadCommand($command) or $this->isWriteCommand($command));
	}

	public function serve() {
	
		// Some background info on how this function is called...
		// * User logs in using an SSH key
		// * The authorized_keys file generated by the function above ensures that a very
		//   specific command is run when that key is used - git-serve.py {userid associated with key}
		// * The git-serve.py script does some sanity checking and runs this command via CakePHP,
		//   passing it the user ID as an argument - the SSH_ORIGINAL_COMMAND environment var is
		//   available as we're being run via SSH
		// * This command does a LOT of sanity and permission checks, then returns the validated command
		//   for the git user to run
		// * git-serve.py then runs that command and the remote git program starts pulling/pushing data
		//
		// ... got it?


		// Firstly, get the SSH_ORIGINAL_COMMAND and other useful variables from environment
		$vars = array_merge($_SERVER, $_ENV);

		if (!isset($vars['SSH_ORIGINAL_COMMAND']) or !isset($vars['argv'])) {
			$this->err("Error: Required environment variables are not defined");
			exit(1);
		}

		$ssh_original_command = $vars['SSH_ORIGINAL_COMMAND']; 
		$argv   = $vars['argv'];
		$userid = array_pop($argv);

		// User ID must be numeric and greater than zero
		if (!preg_match('/^\s*(\d+)\s*$/', $userid, $matches)) {
			$this->err("Error: You do not have the necessary permissions");
			exit(1);
		}
		$userid = $matches[1];

		if ($userid <= 0) {
			$this->err("Error: You do not have the necessary permissions");
			exit(1);
		}

		// Secondly, validate the arguments and get the command into a generic format

		// Check if SSH_ORIGINAL_COMMAND contains newlines and bomb out early (nice easy sanity check)
		if (strpos($ssh_original_command, "\n") !== false) { //!=== as it may also return non-boolean values that evaluate to false
			$this->err("Error: SSH_ORIGINAL_COMMAND contains newlines");
			exit(1);
		}
		
		// If it's a valid git command it will look something like:
		// git-receive-pack 'projects/fnord.git'
		// or:
		// git receive-pack 'projects/fnord.git'

		// Match both forms; command will be in $1, command args will be in $3
		// Bomb out if the command doesn't match
		if (!preg_match('/^\s*(git(\s+|\-)\S+)\s+(.+)$/', $ssh_original_command, $matches)) {
			$this->err("Error: Command is not a valid git command");
			exit(1);
		}

		// Make sure it's in the git-receive-pack format, not git receive-pack
		$command = preg_replace('/\s+/', '-', $matches[1]);

		// Remove any quotes around the command arguments (go go gadget irregular expressions!)
		$args	= preg_replace('/^(\'|\")(.+)\\1/', '\\2', $matches[3]);

		// Check if it's a valid git command to start with...
		if (!$this->isValidGitCommand($command)) {
			$this->err("Error: Unknown command");
			exit(1);
		}

		// Now check that they've given us a valid repo name
		// Should look something like:
		// projects/fnord.git
		// ...but actually, we'll just throw away the entire path except for the last part,
		// then make sure it's a valid unix username with '.git' on the end.
		// NB project names are always valid unix usernames
		if (!preg_match('#^(.*/)?([a-zA-Z0-9][a-zA-Z0-9@._-]*).git$#', $args, $matches)) {
			$this->err("Error: Malformed repository name");
			exit(1);
		}

		$_proj_name = $matches[2];
		
		// Try and get project info, if it doesn't exist then don't give that fact away...
		$project = $this->Project->getProject($_proj_name, true);
		if (empty ($project)){
			$this->err("Error: You do not have the necessary permissions");
			exit(1);
		}

		// We don't need to set all the details, just the ID so we can call hasRead/hasWrite
		$this->Project->id = $project['Project']['id'];

		$rt = $this->Project->RepoType->find(
	 		'first', array(
			'conditions' => array('RepoType.id' => $project['Project']['repo_type']),
			'recursive'  => -1
		));

		// Get the repository location
		$sourcekettle_config = $this->getSourceKettleConfig();
		$repo_path = $sourcekettle_config['SourceRepository']['base']['value'] . "/$_proj_name.git";

		// Make sure there's actually a git repository for this project...
		if (strtolower($rt['RepoType']['name']) != 'git' or !is_dir($repo_path)) {
			$this->err(__("Error: You do not have the necessary permissions"));
			exit(1);
		}

		// We already know th ecommand is valid, so it's either a read or a write command...

		// Check read permission
		if ($this->isReadCommand($command) and !$this->Project->hasRead($userid)) {
			$this->err("Error: You do not have the necessary permissions");
			exit(1);

		// Check write permission
		} else if ($this->isWriteCommand($command) and !$this->Project->hasWrite($userid)) {
			$this->err("Error: You do not have the necessary permissions");
			exit(1);

		}

		// Sanity checks complete. Pass through to the git command.
		passthru("$command ".escapeshellarg($repo_path));
	}

	/**
	* Override the default welcome. We do not want to print the welcome message as this breaks git, so do nothing
	*/
	protected function _welcome(){

	}

}

?>
