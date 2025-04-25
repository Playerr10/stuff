<?php

namespace watrlabs;
use PDO;
use watrlabs\logging;
set_error_handler(function ($severity, $message, $file, $line) {
    //$hello = new logging();
    //->errorwebhook('Including file: $file');
    throw new \ErrorException($message, $severity, $severity, $file, $line);
});

class authentication {
    public function __construct() {
        return 'Hello World!';
    }
    
    public function helloworld() {
        return 'Hello World!';
    }
    
    public function requiresession() {
        if(!isset($_COOKIE['watrbxcookie'])){
            ob_clean();
            header('Location: /login');
            exit;
        }
    }
    
    public function requireguest(){
        if(isset($_COOKIE['watrbxcookie'])){
            ob_clean();
            header('Location: /home');
            exit;
        }
    }
    
    public function verifycsrf($token, $form){
        
        if(isset($_COOKIE["watrbxcookie"])){
            
            $userinfo = $this->getuserinfo($_COOKIE["watrbxcookie"]);
            
            include(baseurl . '/conn.php');
            $query = 'SELECT * FROM csrftokens WHERE token = :token AND userid = :userid AND form = :form';
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->bindParam(':userid', $userinfo["id"], PDO::PARAM_STR);
            $stmt->bindParam(':form', $form, PDO::PARAM_STR);
            $stmt->execute();
            $csrfinfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($csrfinfo){
                $query = 'DELETE FROM csrftokens WHERE token = :csrf';
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':csrf', $token, PDO::PARAM_STR);
                $stmt->execute();
            
                setcookie("csrftoken", $token, time() - 120, '');
                return true;
            } else {
                return false;
            }
            
            
        } else {
            die(header("Location: /login")); // again never should be ran but just in case
        }
        
    }
    
    public function createcsrf($form){
        
        if(isset($_COOKIE["watrbxcookie"])){
            
            $csrf = $this->genstring(20);
            $userinfo = $this->getuserinfo($_COOKIE["watrbxcookie"]);
            
            include(baseurl . '/conn.php');
            $query = 'INSERT INTO csrftokens (token, userid, form) VALUES (:csrf, :userid, :form)';
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':csrf', $csrf, PDO::PARAM_STR);
            $stmt->bindParam(':userid', $userinfo["id"], PDO::PARAM_STR);
            $stmt->bindParam(':form', $form, PDO::PARAM_STR);
            $stmt->execute();
            
            setcookie("csrftoken", $csrf, time() + 500, '/'); // csrf tokens and messages have the same expire time :zany:
            
        } else {
            die(header("Location: /login")); // this should never be run but just in case 
        }
        
    }
    
    public function expiresession($session){
        try {
            if(!empty($_COOKIE['watrbxcookie']) && !empty($session)){
                include(baseurl . '/conn.php');
                $query = 'DELETE FROM sessions WHERE id = :session';
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':session', $session, PDO::PARAM_STR);
                $stmt->execute();
                setcookie('session', '', time() - 3600, '/');
                
                $returnvar = array(
                    'code' => '200',
                    'message' => 'Session removed successfully.',
                );
            
                return json_encode($returnvar);
                exit();
            }
            else {
                $returnvar = array(
                    'code' => '400',
                    'message' => 'You have no session to remove.',
                );
            
                return json_encode($returnvar);
                exit();
            }
        } catch (ErrorException $e){
            $returnvar = array(
                'code' => '500',
                'message' => 'An error occured.',
                'error' => "$e"
            );
            
            return json_encode($returnvar);
            errorwebhook("$e");
            exit();
        
        }

    }
    
    //ty https://stackoverflow.com/questions/4356289/php-random-string-generator
    public function genstring($length) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getuserinfo($sessionid) {
        
        if(strpos($sessionid, "Guest") !== false){
            return false;
        }
        
        include(baseurl . '/conn.php');
        
        $session = $pdo->prepare('SELECT * FROM `sessions` WHERE `id` = ?'); 
	    $session->execute([$sessionid]);
	
	    if($session->rowCount() > 0){
		    $sessiondata = $session->fetch(PDO::FETCH_ASSOC);
		    $creator = $pdo->prepare('SELECT id, username, regtime, robux, tix, role, placelimit, builderclub FROM `users` WHERE `id` = :ownid');
		    $creator->bindParam(':ownid', $sessiondata['ownid'], PDO::PARAM_STR);
		    $creator->execute();
		    $userinfo = $creator->fetch(PDO::FETCH_ASSOC);
		    
		    $bancheck = $pdo->prepare('SELECT * FROM `bans` WHERE `userid` = ? AND ignoreaction = 0'); 
	        $bancheck->execute([$userinfo['id']]);
	        
	        if($bancheck->rowCount() > 0){
	            $baninfo = $bancheck->fetch(PDO::FETCH_ASSOC);
	            //echo $_SERVER['REQUEST_URI'];
	            if($_SERVER['REQUEST_URI'] == "/unapproved?id=" . $baninfo['id']){
	                
	            } elseif($_SERVER["REQUEST_URI"] == "/account/reactivate?banid=" . $baninfo['id']){
                    
                } else {
                    header('Location: /unapproved?id=' . $baninfo['id']);
	                die();
                }
	            http_response_code(403);
	            //die();
	        }
		    
		    return $userinfo; //returns in an array (e.g $userinfo['username'] )
	    } else {
	        setcookie("watrbxcookie", "", time() - 99999, "/");
	        header("Location: /login");
	    }
    }

    public function checkapikey($apikey){
        include(baseurl . '/conn.php');
        
        $session = $pdo->prepare('SELECT * FROM `apikeys` WHERE `apikey` = ?'); 
	    $session->execute([$apikey]);
	
	    if($session->rowCount() > 0){
		    $sessiondata = $session->fetch(PDO::FETCH_ASSOC);
		    $creator = $pdo->prepare('SELECT * FROM apikeys WHERE apikey = :apikey');
		    $creator->bindParam(':apikey', $apikey, PDO::PARAM_STR);
		    $creator->execute();
		    $userinfo = $creator->fetch(PDO::FETCH_ASSOC);

            $isvalid = $userinfo['valid'];
            
            if($isvalid !== 1){
                $returnvar = array(
                    'code' => '400',
                    'message' => 'apikey invalid.',
                );
            
                return json_encode($returnvar);
                exit();
            }
            else {
                return true;
            }
		    
		    //returns in an array (e.g $userinfo['username'] )
	    }
    }
    
    public function sendverifemail($email, $ownid) {
        
        //this needs to be fixed

        $email = new email();
        $gen = new authentication();
        $genid = $gen->genstring(30);
        $subject = 'WatrLabs verification email';
        
        $message = '<h1>Welcome to Watrlabs</h1>';
        $message .= '<h2>Please click the link provided below.</h2>';
        $message .= '<p>Your email verification link: <a href=\'https://watrlabs.lol/verify?code=$genid\'></a></p>';
        
        $email->sendmail($email, $subject, $message);
        $email->sendmail($email, $subject, $message);
        include(baseurl . '/conn.php');
        $query = 'INSERT INTO emailcodes (id, code, ownerid, verified) VALUES (NULL, :code, :ownerid, NULL)';
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':code', $genid, PDO::PARAM_STR);
        $stmt->bindParam(':ownid', $ownid, PDO::PARAM_STR);
        $stmt->execute();
        
        
    }
    
    public function createsession($id){
        $gen = new authentication();
        $genid = $gen->genstring(25);
        $os = $gen->getOS();
        
        include(baseurl . '/conn.php');
        $regtime = time();
        $query = 'INSERT INTO sessions (id, ownid, platform, time) VALUES (:genid, :ownid, :platform, :regtime)';
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':genid', $genid, PDO::PARAM_STR);
        $stmt->bindParam(':ownid', $id, PDO::PARAM_STR);
        $stmt->bindParam(':platform', $os, PDO::PARAM_STR);
        $stmt->bindParam(':regtime', $regtime, PDO::PARAM_INT);
        $stmt->execute();
                
        return $genid;
    }

    public function login($username, $password){

        if(empty($username)){
            $returnvar = array(
            'code' => '400',
            'message' => 'Username is empty.',
            );
            
            return json_encode($returnvar);
            exit();
        }

        if(empty($password)){
            if(empty($username)){
            $returnvar = array(
            'code' => '400',
            'message' => 'Username is empty.',
            );
            
            return json_encode($returnvar);
            exit();
        }

        }

        include(baseurl . '/conn.php');
        $stmt = $pdo->prepare('SELECT username FROM users WHERE username = ?');
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            $getuserinfo = $pdo->prepare('SELECT id, username, password, role FROM `users` WHERE `username` = :username');
		    $getuserinfo->bindParam(':username', $username, PDO::PARAM_STR);
		    $getuserinfo->execute();
		    $userinfo = $getuserinfo->fetch(PDO::FETCH_ASSOC);

            if(password_verify($password, $userinfo['password'])){
                
                //if($userinfo["role"] !== 1){
                //    $returnvar = array(
                //        'code' => '403',
                //        'message' => 'Only admins can login currently.',
                //    );
            
                //    return json_encode($returnvar);
                //    exit();
                //}
                
                $tokengen = new authentication();
                $session = $tokengen->createsession($userinfo['id']);
                
                $returnvar = array(
                    'code' => '200',
                    'message' => 'Login succesfull',
                    'token' => "$session"
                );

                return json_encode($returnvar);
                exit();
            }
            else {
                $returnvar = array(
                    'code' => '400',
                    'message' => 'Password is incorrect.',
                );
                            
                return json_encode($returnvar);
                exit();
            }
        } else {
            $returnvar = array(
                'code' => '400',
                'message' => 'Username does not exist.',
            );
                        
            return json_encode($returnvar);
            exit();
        }
        

    }

    public function createuser($username, $password, $confpasswordd) {
        include(baseurl . '/conn.php');
        // they're all already put in variables for us :party:
            
            $stmt = $pdo->prepare('SELECT username FROM users WHERE username = ?');
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $returnvar = array(
                    'code' => '400',
                    'message' => 'Username is already taken.',
                );
                    
                return json_encode($returnvar);
                exit();
            }
            
            $stmt = $pdo->prepare('SELECT username FROM users WHERE username = ?');
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $returnvar = array(
                    'code' => '400',
                    'message' => 'Username is already taken.',
                );
                    
                return json_encode($returnvar);
                exit();
            }
            
            
            if(empty($username)){
                $returnvar = array(
                    'code' => '400',
                    'message' => 'Username is empty.',
                );
                
                return json_encode($returnvar);
                exit();
            }
            
            if(empty($password)){
                $returnvar = array(
                'code' => '400',
                'message' => 'Password is empty.',
                );
                
                return json_encode($returnvar);
                exit();
            }
            
            if(empty($confpasswordd)){
                $returnvar = array(
                'code' => '400',
                'message' => 'Password confirmation is empty.',
                );
                
                return json_encode($returnvar);
                exit();
            }
        
            if (!preg_match("/^[a-zA-Z0-9]+$/", $username))
            {
                $returnvar = array(
                'code' => '400',
                'message' => 'Username contains special characters.',
                );
                
                return json_encode($returnvar);
                exit();
            }
            
            if(preg_match("/\s/",$username)){
                $returnvar = array(
                    'code' => '400',
                    'message' => 'Username contains special characters.',
                );
                return json_encode($returnvar);
                exit();
            }
            
            if (strlen($username) >= 21){
                $returnvar = array(
                'code' => '400',
                'message' => 'Username is too long.',
                );
                
                return json_encode($returnvar);
                exit();
            }
            
            if (strlen($username) <= 2){
                $returnvar = array(
                'code' => '400',
                'message' => 'Username is too short.',
                );
                
                return json_encode($returnvar);
                exit();
            }
            
            if (strlen($password) > 200){
                $returnvar = array(
                'code' => '400',
                'message' => 'Password is too long.',
                );
                
                return json_encode($returnvar);
                exit();
            }
            
            if (strlen($password) <= 3){
                $returnvar = array(
                'code' => '400',
                'message' => 'Password is too short. Must be 4 characters or longer.',
                );
                
                return json_encode($returnvar);
                exit();
            }
            
            if ($password !== $confpasswordd){
                $returnvar = array(
                'code' => '400',
                'message' => 'Passwords do not match.',
                );
                
                return json_encode($returnvar);
                exit();
            }
            
            
            $stmt = $pdo->prepare('SELECT username FROM users WHERE username = ?');
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $returnvar = array(
                    'code' => '400',
                    'message' => 'Username is already taken.',
                );
                    
                return json_encode($returnvar);
                exit();
            }
            
            $stmt = $pdo->prepare('SELECT username FROM users WHERE username = ?');
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $returnvar = array(
                    'code' => '400',
                    'message' => 'Username is already taken.',
                );
                    
                return json_encode($returnvar);
                exit();
            }
            
            $stmt = $pdo->prepare('SELECT username FROM users WHERE username = ?');
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $returnvar = array(
                    'code' => '400',
                    'message' => 'Username is already taken.',
                );
                    
                return json_encode($returnvar);
                exit();
            }
            
            $stmt = $pdo->prepare('SELECT username FROM users WHERE username = ?');
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $returnvar = array(
                    'code' => '400',
                    'message' => 'Username is already taken.',
                );
                    
                return json_encode($returnvar);
                exit();
            }
            
            $stmt = $pdo->prepare('SELECT username FROM users WHERE username = ?');
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $returnvar = array(
                    'code' => '400',
                    'message' => 'Username is already taken.',
                );
                    
                return json_encode($returnvar);
                exit();
            }
            
            
            $randdd = rand(0,2);
            sleep($randdd);
            
            $stmt = $pdo->prepare('SELECT username FROM users WHERE username = ?');
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $returnvar = array(
                    'code' => '400',
                    'message' => 'Username is already taken.',
                );
                    
                return json_encode($returnvar);
                exit();
            }
            
            
            $current_time = time();
            $limit_time = $current_time - 60;

            $stmt = $pdo->prepare('SELECT COUNT(*) as post_count FROM users WHERE regtime >= :limit_time');
            $stmt->bindParam(':limit_time', $limit_time, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $post_count = $row['post_count'];

            if ($post_count >= 5) {
                $returnvar = array(
                    'code' => '429',
                    'message' => 'Too many accounts have been created recently, please come back later.',
                );
                    
                return json_encode($returnvar);
                exit();
            }
            
            
            
            // at this point I think we should be good so time to hash and INSERT
            try {
                $password = password_hash($password, PASSWORD_BCRYPT);
            } catch (ErrorException $e){
                $returnvar = array(
                'code' => '500',
                'message' => 'An error occured when creating your account. The server returned $e',
                );
                
                return json_encode($returnvar);
                exit();
            }
            
            try {
                $gen = new authentication();
                $genid = $gen->genstring(25);
            } catch (ErrorException $e){
                $returnvar = array(
                'code' => '500',
                'message' => 'An error occured generating your unique identifier. The server returned $e',
                );
                
                return json_encode($returnvar);
                exit();
            }
            try {
                
                $regtime = time();
                
                $stmt = $pdo->prepare('SELECT username FROM users WHERE username = ?');
                $stmt->execute([$username]);
            
                if ($stmt->rowCount() > 0) {
                    $returnvar = array(
                        'code' => '400',
                        'message' => 'Username is already taken.',
                    );
                    
                    return json_encode($returnvar);
                    exit();
                }
            
                $query = 'INSERT INTO users (username, password, regtime) VALUES (:username, :password, :regtime)';
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':password', $password, PDO::PARAM_STR);
                $stmt->bindParam(':regtime', $regtime, PDO::PARAM_INT);
                $stmt->execute();
                
                try {
                    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE username = ?');
                    $stmt->execute([$username]);
            
                    if ($stmt->rowCount() > 0) {
                        $userinfo = $stmt->fetch(PDO::FETCH_ASSOC);
                        $id = $userinfo["id"];
                    }
                }
                catch(PDOexception $e){
                    $returnvar = array(
                'code' => '500',
                'message' => $e
                );
                
                return json_encode($returnvar);
                exit();
                }
                
                
                $tokengen = new authentication();
                $session = $tokengen->createsession($id);
                
                //$email = new authentication();
                
                //$email->sendverifemail($email, $genid);
                
                $logging = new logging();
                $logging->logwebhook("$username has registered to the site!");
                
                $returnvar = array(
                'code' => '200',
                'message' => 'Your account was made successfully',
                'token' => $session
                );
                
                return json_encode($returnvar);
                exit();
            } catch (ErrorException $e){
                $returnvar = array(
                'code' => '500',
                'message' => 'An error occured saving your account. The server returned $e',
                );
                
                return json_encode($returnvar);
                exit();
            }
    }
    
    public function getOS($user_agent = null) {
        
    if(!isset($user_agent) && isset($_SERVER['HTTP_USER_AGENT'])) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
    }

    // https://stackoverflow.com/questions/18070154/get-operating-system-info-with-php
    $os_array = [
        'windows nt 10'                              =>  'Windows 10',
        'windows nt 6.3'                             =>  'Windows 8.1',
        'windows nt 6.2'                             =>  'Windows 8',
        'windows nt 6.1|windows nt 7.0'              =>  'Windows 7',
        'windows nt 6.0'                             =>  'Windows Vista',
        'windows nt 5.2'                             =>  'Windows Server 2003/XP x64',
        'windows nt 5.1'                             =>  'Windows XP',
        'windows xp'                                 =>  'Windows XP',
        'windows nt 5.0|windows nt5.1|windows 2000'  =>  'Windows 2000',
        'windows me'                                 =>  'Windows ME',
        'windows nt 4.0|winnt4.0'                    =>  'Windows NT',
        'windows ce'                                 =>  'Windows CE',
        'windows 98|win98'                           =>  'Windows 98',
        'windows 95|win95'                           =>  'Windows 95',
        'win16'                                      =>  'Windows 3.11',
        'mac os x 10.1[^0-9]'                        =>  'Mac OS X Puma',
        'macintosh|mac os x'                         =>  'Mac OS X',
        'mac_powerpc'                                =>  'Mac OS 9',
        'ubuntu'                                     =>  'Linux - Ubuntu',
        'iphone'                                     =>  'iPhone',
        'ipod'                                       =>  'iPod',
        'ipad'                                       =>  'iPad',
        'android'                                    =>  'Android',
        'blackberry'                                 =>  'BlackBerry',
        'webos'                                      =>  'Mobile',
        'linux'                                      =>  'Linux',

        '(media center pc).([0-9]{1,2}\.[0-9]{1,2})'=>'Windows Media Center',
        '(win)([0-9]{1,2}\.[0-9x]{1,2})'=>'Windows',
        '(win)([0-9]{2})'=>'Windows',
        '(windows)([0-9x]{2})'=>'Windows',

        // Doesn't seem like these are necessary...not totally sure though..
        //'(winnt)([0-9]{1,2}\.[0-9]{1,2}){0,1}'=>'Windows NT',
        //'(windows nt)(([0-9]{1,2}\.[0-9]{1,2}){0,1})'=>'Windows NT', // fix by bg

        'Win 9x 4.90'=>'Windows ME',
        '(windows)([0-9]{1,2}\.[0-9]{1,2})'=>'Windows',
        'win32'=>'Windows',
        '(java)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2})'=>'Java',
        '(Solaris)([0-9]{1,2}\.[0-9x]{1,2}){0,1}'=>'Solaris',
        'dos x86'=>'DOS',
        'Mac OS X'=>'Mac OS X',
        'Mac_PowerPC'=>'Macintosh PowerPC',
        'CrOs'=>"Chrome OS",
        '(mac|Macintosh)'=>'Mac OS',
        '(sunos)([0-9]{1,2}\.[0-9]{1,2}){0,1}'=>'SunOS',
        '(beos)([0-9]{1,2}\.[0-9]{1,2}){0,1}'=>'BeOS',
        '(risc os)([0-9]{1,2}\.[0-9]{1,2})'=>'RISC OS',
        'unix'=>'Unix',
        'os/2'=>'OS/2',
        'freebsd'=>'FreeBSD',
        'openbsd'=>'OpenBSD',
        'netbsd'=>'NetBSD',
        'irix'=>'IRIX',
        'plan9'=>'Plan9',
        'osf'=>'OSF',
        'aix'=>'AIX',
        'GNU Hurd'=>'GNU Hurd',
        '(fedora)'=>'Linux - Fedora',
        '(kubuntu)'=>'Linux - Kubuntu',
        '(ubuntu)'=>'Linux - Ubuntu',
        '(debian)'=>'Linux - Debian',
        '(CentOS)'=>'Linux - CentOS',
        '(Mandriva).([0-9]{1,3}(\.[0-9]{1,3})?(\.[0-9]{1,3})?)'=>'Linux - Mandriva',
        '(SUSE).([0-9]{1,3}(\.[0-9]{1,3})?(\.[0-9]{1,3})?)'=>'Linux - SUSE',
        '(Dropline)'=>'Linux - Slackware (Dropline GNOME)',
        '(ASPLinux)'=>'Linux - ASPLinux',
        '(Red Hat)'=>'Linux - Red Hat',
        // Loads of Linux machines will be detected as unix.
        // Actually, all of the linux machines I've checked have the 'X11' in the User Agent.
        //'X11'=>'Unix',
        '(linux)'=>'Linux',
        '(amigaos)([0-9]{1,2}\.[0-9]{1,2})'=>'AmigaOS',
        'amiga-aweb'=>'AmigaOS',
        'amiga'=>'Amiga',
        'AvantGo'=>'PalmOS',
        //'(Linux)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3}(rel\.[0-9]{1,2}){0,1}-([0-9]{1,2}) i([0-9]{1})86){1}'=>'Linux',
        //'(Linux)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3}(rel\.[0-9]{1,2}){0,1} i([0-9]{1}86)){1}'=>'Linux',
        //'(Linux)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3}(rel\.[0-9]{1,2}){0,1})'=>'Linux',
        '[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3}'=>'Linux',
        '(webtv)/([0-9]{1,2}\.[0-9]{1,2})'=>'WebTV',
        'Dreamcast'=>'Dreamcast OS',
        'GetRight'=>'Windows',
        'go!zilla'=>'Windows',
        'gozilla'=>'Windows',
        'gulliver'=>'Windows',
        'ia archiver'=>'Windows',
        'NetPositive'=>'Windows',
        'mass downloader'=>'Windows',
        'microsoft'=>'Windows',
        'offline explorer'=>'Windows',
        'teleport'=>'Windows',
        'web downloader'=>'Windows',
        'webcapture'=>'Windows',
        'webcollage'=>'Windows',
        'webcopier'=>'Windows',
        'webstripper'=>'Windows',
        'webzip'=>'Windows',
        'wget'=>'Windows',
        'Java'=>'Unknown',
        'flashget'=>'Windows',

        // delete next line if the script show not the right OS
        //'(PHP)/([0-9]{1,2}.[0-9]{1,2})'=>'PHP',
        'MS FrontPage'=>'Windows',
        '(msproxy)/([0-9]{1,2}.[0-9]{1,2})'=>'Windows',
        '(msie)([0-9]{1,2}.[0-9]{1,2})'=>'Windows',
        'libwww-perl'=>'Unix',
        'UP.Browser'=>'Windows CE',
        'NetAnts'=>'Windows',
    ];

    // https://github.com/ahmad-sa3d/php-useragent/blob/master/core/user_agent.php
    $arch_regex = "/\b(x86_64|x86-64|Win64|WOW64|x64|ia64|amd64|ppc64|sparc64|IRIX64)\b/ix";
    $arch = preg_match($arch_regex, $user_agent) ? '64' : '32';

    foreach ($os_array as $regex => $value) {
        if (preg_match('{\b('.$regex.')\b}i', $user_agent)) {
            return $value.' x'.$arch;
        }
    }

    return 'Unknown';
}
}
