<?php
    use watergames\pagebuilder;
    $pagebuilder = new pagebuilder;
    use watrlabs\authentication;
    $auth = new authentication();
    $auth->requiresession();
    $userinfo = $auth->getuserinfo($_COOKIE["watrbxcookie"]);
    $pagebuilder->set_page_name("Unapproved");
    $pagebuilder->buildheader();
    include(baseurl . "/conn.php");
    $bancheck = $pdo->prepare('SELECT * FROM `bans` WHERE `userid` = ? AND ignoreaction = 0'); 
    $bancheck->execute([$userinfo['id']]);
	        
    if($bancheck->rowCount() > 0){
        $baninfo = $bancheck->fetch(PDO::FETCH_ASSOC);
	    
        if($_SERVER['REQUEST_URI'] !== "/unapproved?id=" . $baninfo['id']){
            header('Location: /unapproved?id=' . $baninfo['id']);
            die();
	   }
        http_response_code(403);
    } else {
        die(header("Location: /home"));
    }

?>

<div id="main">

    <?php
        if($baninfo["bantype"] == "warning"){ ?>
    
            <h1>Warning</h1>
            <p>Moderation action has been taken against your account!</p>
            <p>Moderator Note: <i><?=$baninfo["reason"]?></i></p>
            <a href="/account/reactivate?banid=<?=$baninfo["id"]?>" class="button" style="margin-left: 0px;">Reactive Account</a>


        <? } elseif($baninfo["bantype"] == "tempban"){ ?>

            <h1>Temp Bam</h1>
            <p>You have temporarily been banned from watrbx.</p>
            <p>Moderator Note: <i><?=$baninfo["reason"]?></i></p>
            

            <? if(time() > $baninfo["expiration"]){ ?>
                <a href="/account/reactivate?banid=<?=$baninfo["id"]?>">Reactive Account</a>
            <? }  ?>

            <br><br>
            <p>If you would like to appeal, join our discord or <a href="mailto:watrbxappeal@watrlabs.lol">email us</a>.</p>
        <? } else {
            echo "<p>You have banned from watrbx.</p><p>Moderator Note: <i>".$baninfo["reason"]."</i></p><p>Your account cannot be activated.</p><br><br><p>If you would like to appeal, join our discord or <a href=\"mailto:watrbxappeal@watrlabs.lol\">email us</a>.</p>";
        }
    ?>

</div>

<? $pagebuilder->get_snippet("footer"); ?>