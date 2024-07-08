<?php
    session_start();
     
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
        echo "<script> alert('You\'re not logged in!\\nLogin from the Homepage to access the Online Multiplayer.'); </script>";
        echo "<script> parent.open_page('home.php') </script>";
        exit;
    }

    require_once 'connect.php';
    $stmt = $conn->query("USE dataset");

    $stmt = $conn->prepare("SELECT * FROM Contestants");
    $stmt->execute();
    $characters = $stmt->fetchAll();

    $stmt = $conn->prepare("SELECT * FROM Skills");
    $stmt->execute();
    $skills = $stmt->fetchAll();

    $stmt = $conn->prepare("SELECT * FROM Weapons");
    $stmt->execute();
    $weapons = $stmt->fetchAll();

    $stmt = $conn->prepare("SELECT * FROM Headarmor");
    $stmt->execute();
    $headarmor = $stmt->fetchAll();

    $stmt = $conn->prepare("SELECT * FROM Bodyarmor");
    $stmt->execute();
    $bodyarmor = $stmt->fetchAll();

    $stmt = $conn->prepare("SELECT * FROM Acessory");
    $stmt->execute();
    $acessory = $stmt->fetchAll();

    $stmt = $conn->prepare("SELECT * FROM Pfps");
    $stmt->execute();
    $pfps = $stmt->fetchAll();

    $stmt = $conn->query("USE playerset");

    $stmt = $conn->prepare("SELECT UserId, UserName, UserPfp FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();

    $sql = "SELECT * FROM Teams WHERE UserId = :id";

    if($stmt = $conn->prepare($sql)) {
        $stmt->bindParam(":id", $param_id, PDO::PARAM_STR);
        
        $param_id = $_SESSION["id"];
    }
    $stmt->execute();
    $teamlist = $stmt->fetchAll();
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
        <title>Lobby</title>
        <link rel="stylesheet" href="styles/battle/play.css">
        <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript">
            var characterList = <?php echo json_encode($characters); ?>;
            var skillList = <?php echo json_encode($skills); ?>;
            var weaponList = <?php echo json_encode($weapons); ?>;
            var headarmorList = <?php echo json_encode($headarmor); ?>;
            var bodyarmorList = <?php echo json_encode($bodyarmor); ?>;
            var acessoryList = <?php echo json_encode($acessory); ?>;
            var pfpList = <?php echo json_encode($pfps); ?>;
            var userList = <?php echo json_encode($users); ?>;
            var teamList = <?php echo json_encode($teamlist); ?>;

            const teamSize = 4;
            var currentTeams = teamList.length;
            var selectedTeam = 0;

            function createTeamSelect() {
                var holder = document.getElementById('teamSelectHolder');
                var selectList = document.createElement("select");
                selectList.id = "teamSelect";
                holder.appendChild(selectList);

                for(var i = 0; i < currentTeams; i++) {
                    var team = document.createElement("option");
                    team.value = i;
                    if(teamList[i][2] != null) team.innerHTML += teamList[i][2] + "<br>";
                    else team.innerHTML += "Untitled " + (i+1) + "<br>";
                    selectList.appendChild(team);
                }

                var teamPrice = document.getElementById("teamPrice");
                teamPrice.innerHTML = "[ "+pricePreview(0)+" $ ]";
                document.getElementById("teamSelect").onchange = function() {selectedTeam = this.value; teamPrice.innerHTML = "[ "+pricePreview(this.value)+" $ ]"};
            }

            function pricePreview(teamId) {
                const team = teamList[teamId];
                var finalPrice = 0;

                for(var i = 0; i < 4; i++) {
                    if(team[i+3] != null) {
                        var importText = team[i+3];
                        var importArray = importText.split('\n');
                        var character = importArray[0][0];
                        var skillCount = characterList[character-1][4];

                        var characterPrice = characterList[character-1][18];
                        var skillPrice = 0;
                        var equipmentPrice = 0;
                        var totalPrice = 0;

                        for (var j = 0; j < skillCount; j++) {
                            if(importArray[j+1][importArray[j+1].length-2] == 'u') skillPrice += skillList[(characterList[character-1][j+5])-1][7];
                        }

                        j++;
                        var weapon = importArray[j][importArray[j].length-1];
                        if(weapon != '0') equipmentPrice += weaponList[weapon-1][5];

                        j++;
                        var headarmor = importArray[j][importArray[j].length-1];
                        if(headarmor != '0') equipmentPrice += headarmorList[headarmor-1][9];

                        j++;
                        var bodyarmor = importArray[j][importArray[j].length-1];
                        if(bodyarmor != '0') equipmentPrice += bodyarmorList[bodyarmor-1][9];

                        j++;
                        var acessory = importArray[j][importArray[j].length-1];
                        if(acessory != '0') equipmentPrice += acessoryList[acessory-1][9];

                        totalPrice = ((characterPrice/10)*(skillPrice + equipmentPrice))/4 + characterPrice;
                        finalPrice += totalPrice;
                    }
                }
                return finalPrice;     
            }

            var battleSearch = null;
            function beginSearch() {
                var format = parseInt(document.getElementById("formatSelect").value);
                if((teamList[selectedTeam][3] != null) || (teamList[selectedTeam][4] != null) || (teamList[selectedTeam][5] != null) || (teamList[selectedTeam][6] != null)){
                    if(format >= pricePreview(selectedTeam)){
                        cancelSearch();
                        battleSearch = setInterval(findBattle, 3000);
                        var playButton = document.getElementById("playButton");
                        var cancelButton = document.getElementById("cancelButton");
                        var teamSelect = document.getElementById("teamSelect");
                        playButton.innerHTML = "Searching...";
                        playButton.disabled = true;
                        cancelButton.classList.remove("hidden");
                        teamSelect.disabled = true; 
                    }
                    else alert("Your team is too expensive for the selected format!");
                }
                else alert("Your team needs to have at least one member to be usable!");
            }

            async function cancelSearch() {
                clearInterval(battleSearch);
                clearInterval(idleTimerW);
                var playButton = document.getElementById("playButton");
                var cancelButton = document.getElementById("cancelButton");
                var teamSelect = document.getElementById("teamSelect");
                playButton.innerHTML = "<span class=\"btnTitle\">Battle!</span><br>Search for a random contestant";
                playButton.disabled = false;
                cancelButton.classList.add("hidden");
                playButton.disabled = false;
                teamSelect.disabled = false;

                $.ajax({
                    type :'POST',
                    data :'',
                    url  : 'cancel_battle.php', 
                    dataType : 'json',
                    encode : true,
                    error : function(e){
                        console.log(e);
                    }
                });
            }

            async function findBattle() {
                var chosenformat = parseInt(document.getElementById("formatSelect").value);
                $.ajax({
                    type :'POST',
                    data :
                        {
                            team: teamList[selectedTeam][3] + "§" + teamList[selectedTeam][4] + "§" + teamList[selectedTeam][5] + "§" + teamList[selectedTeam][6],
                            format: chosenformat
                        },
                    url  : 'find_battle.php', 
                    dataType : 'json',
                    encode : true,
                    success : function(lobbyState){
                        var playButton = document.getElementById("playButton");
                        var cancelButton = document.getElementById("cancelButton");

                        switch(lobbyState) {
                            case 1:
                                playButton.innerHTML = "Opponent found!";
                                cancelButton.classList.add("hidden");
                                startBattle();
                                break;
                            default:
                                break;
                        }
                    },
                    error : function(e){
                        console.log(e);
                    }
                });

            }

            function mainMenu() {
                cancelSearch();
                parent.open_page('home.php');

            }

            function swapState(state) {
                switch(state) {
                    case 0:
                        document.getElementById("lobby").classList.remove("hidden");
                        document.getElementById("battle").classList.add("hidden");
                        break;
                    case 1:
                        document.getElementById("lobby").classList.add("hidden");
                        document.getElementById("battle").classList.remove("hidden");
                        break;
                }
            }

            var playerSide = null;
            var lobbyId = null;
            var p1Id = null;
            var p2Id = null;
            var p1Team = null;
            var p2Team = null;

            function startBattle() {
                clearInterval(battleSearch);
                swapState(1);
                getBattleData();
                playMusic("monologue.ogg");
            }

            function loadPlayerData() {
                var p1LocalId = null;
                var p2LocalId = null;
                var p1PfpId = null;
                var p2PfpId = null;

                for(var i = 0; i < userList.length; i++) {
                    if(userList[i][0] == p1Id) p1LocalId = i;
                    if(userList[i][0] == p2Id) p2LocalId = i;
                }

                for(var i = 0; i < pfpList.length; i++) {
                    if(pfpList[i][0] == userList[p1LocalId][2]) p1PfpId = i;
                    if(pfpList[i][0] == userList[p2LocalId][2]) p2PfpId = i;
                }
                var p1Name = userList[p1LocalId][1];
                var p2Name = userList[p2LocalId][1];
                var p1Pfp = pfpList[p1PfpId][1];
                var p2Pfp = pfpList[p2PfpId][1];

                switch(playerSide) {
                    case 0:
                        document.getElementById("playerSide").innerHTML = p1Name + "<br><br><img src='" + p1Pfp +"' style='max-height: 300px; max-width: 300px;'>";
                        document.getElementById("enemySide").innerHTML = p2Name + "<br><br><img src='" + p2Pfp +"' style='max-height: 300px; max-width: 300px;'>";
                        break;

                    default:
                        document.getElementById("playerSide").innerHTML = p2Name + "<br><br><img src='" + p2Pfp +"' style='max-height: 300px; max-width: 300px;'>";
                        document.getElementById("enemySide").innerHTML = p1Name + "<br><br><img src='" + p1Pfp +"' style='max-height: 300px; max-width: 300px;'>";
                        break;
                }

                p1Team = importCharacters(p1Team);
                p2Team = importCharacters(p2Team);
                loadContestants();
                showContestants();
            }

            async function getBattleData() {
                $.ajax({
                    type :'POST',
                    data :'',
                    url  : 'get_battle_data.php', 
                    dataType : 'json',
                    encode : true,
                    success : function(data){
                        playerSide = data[0];
                        lobbyId = data[1];
                        p1Id = data[2];
                        p2Id = data[3];
                        p1Team = data[4].split("§");
                        p2Team = data[5].split("§");

                        loadPlayerData();
                    },
                    error : function(e){
                        console.log(e);
                    }
                });
            }

            function importCharacters(team) {
                for(var i = 0; i < teamSize; i++) {
                    if(team[i] != "null") {
                        var importText = team[i];
                        var importArray = importText.split('\n');
                        var skillCount = characterList[importArray[0][0]-1][4];

                        var chrId = importArray[0][0];
                        var nickname = importArray[0].substring(importArray[0].indexOf("(")+1, importArray[0].indexOf(")"));
                        if(nickname == "") nickname = characterList[importArray[0][0]-1][2];

                        var skills = [false];
                        for (var j = 0; j < skillCount; j++) {
                            if(importArray[j+1][importArray[j+1].length-2] == 'u') {
                                skills[j] = true;
                            }
                            else skills[j] = false; 
                        }

                        j++;
                        var weaponAnim = null
                        var weapon = importArray[j][importArray[j].length-1];
                        if(weapon == '0') {
                            weapon = "none";
                            weaponAnim = "blunt";
                        }
                        else weaponAnim = weaponList[weapon-1][3];

                        j++;
                        var headarmor = importArray[j][importArray[j].length-1];
                        if(headarmor == '0') headarmor = "none";

                        j++;
                        var bodyarmor = importArray[j][importArray[j].length-1];
                        if(bodyarmor == '0') bodyarmor = "none";

                        j++;
                        var acessory = importArray[j][importArray[j].length-1];
                        if(acessory == '0') acessory = "none";

                        var maxHp, hp;
                        maxHp = hp = characterList[chrId-1][11];
                        var maxMind, mind;
                        maxMind = mind = characterList[chrId-1][12];

                        var bonusAtk = 0;
                        if(weapon != "none") bonusAtk += weaponList[weapon-1][5];
                        if(headarmor != "none") bonusAtk += headarmorList[headarmor-1][3]; 
                        if(bodyarmor != "none") bonusAtk += bodyarmorList[bodyarmor-1][3]; 
                        if(acessory != "none") bonusAtk += acessoryList[acessory-1][3];

                        var bonusDef = 0;
                        if(headarmor != "none") bonusDef += headarmorList[headarmor-1][4]; 
                        if(bodyarmor != "none") bonusDef += bodyarmorList[bodyarmor-1][4]; 
                        if(acessory != "none") bonusDef += acessoryList[acessory-1][4];

                        var bonusMAtk = 0;
                        if(headarmor != "none") bonusMAtk += headarmorList[headarmor-1][5]; 
                        if(bodyarmor != "none") bonusMAtk += bodyarmorList[bodyarmor-1][5]; 
                        if(acessory != "none") bonusMAtk += acessoryList[acessory-1][5];

                        var bonusMDef = 0;
                        if(headarmor != "none") bonusMDef += headarmorList[headarmor-1][6]; 
                        if(bodyarmor != "none") bonusMDef += bodyarmorList[bodyarmor-1][6]; 
                        if(acessory != "none") bonusMDef += acessoryList[acessory-1][6];

                        var bonusAgi = 0;
                        if(headarmor != "none") bonusAgi += headarmorList[headarmor-1][7]; 
                        if(bodyarmor != "none") bonusAgi += bodyarmorList[bodyarmor-1][7]; 
                        if(acessory != "none") bonusAgi += acessoryList[acessory-1][7];

                        var bonusRes = 0;
                        if(headarmor != "none") bonusRes += headarmorList[headarmor-1][8]; 
                        if(bodyarmor != "none") bonusRes += bodyarmorList[bodyarmor-1][8]; 
                        if(acessory != "none") bonusRes += acessoryList[acessory-1][8];

                        var finalAtk = characterList[chrId-1][13] + bonusAtk;
                        var finalDef = parseInt(((characterList[chrId-1][14] + bonusDef) * (100 + bonusRes)) / 100);
                        var finalMAtk = characterList[chrId-1][15] + bonusMAtk;
                        var finalMDef = parseInt(((characterList[chrId-1][16] + bonusMDef) * (100 + bonusRes)) / 100);
                        var finalAgi = characterList[chrId-1][17] + bonusAgi;

                        var isGuarding = false;
                        var status = [[]];

                        team[i] = null;
                        team[i] = [chrId, nickname, skills, weaponAnim, maxHp, hp, maxMind, mind, finalAtk, finalDef, finalMAtk, finalMDef, finalAgi, isGuarding, status];
                        team[i][14].pop();
                    }
                }
                return team;
            }

            // BATTLE FUNCTIONS
            var playerTurn = "";
            var characterTurn = 0;
            var prevTurn = 0;

            function loadContestants() {
                var pContSprt = [null];
                var eContSprt = [null];

                switch(playerSide) {
                    case 0:
                        for(var i = 0; i < teamSize; i++) {
                            if(p1Team[i] != "null") pContSprt[i] = characterList[p1Team[i][0]-1][20];
                            else pContSprt[i] = "https://i.postimg.cc/HsbpN1my/back-none.png";
                            if(p2Team[i] != "null") eContSprt[i] = characterList[p2Team[i][0]-1][19];
                            else eContSprt[i] = "https://i.postimg.cc/2SzfXqTs/front-none.png";
                        }       
                        break;

                    default:                       
                        for(var i = 0; i < teamSize; i++) {
                            if(p2Team[i] != "null") pContSprt[i] = characterList[p2Team[i][0]-1][20];
                            else pContSprt[i] = "https://i.postimg.cc/HsbpN1my/back-none.png";
                            if(p1Team[i] != "null") eContSprt[i] = characterList[p1Team[i][0]-1][19];
                            else eContSprt[i] = "https://i.postimg.cc/2SzfXqTs/front-none.png";
                        }
                        break;
                }

                document.getElementById("pCont1").src = pContSprt[0];
                document.getElementById("pCont2").src = pContSprt[1];
                document.getElementById("pCont3").src = pContSprt[2];
                document.getElementById("pCont4").src = pContSprt[3];
                document.getElementById("eCont1").src = eContSprt[0];
                document.getElementById("eCont2").src = eContSprt[1];
                document.getElementById("eCont3").src = eContSprt[2];
                document.getElementById("eCont4").src = eContSprt[3];
            }

            function showContestants() {
                var txt = "";

                switch(playerSide) {
                    case 0:
                        for(var i = 0; i < teamSize; i++) if(p1Team[i] != "null") txt += "<span id='nChr"+i+"'>"+p1Team[i][1]+"</span> // Body: "+p1Team[i][5]+"/"+p1Team[i][4]+" // Mind: "+p1Team[i][7]+"/"+p1Team[i][6]+"<br>";     
                        break;

                    default:                       
                        for(var i = 0; i < teamSize; i++) if(p2Team[i] != "null") txt += "<span id='nChr"+i+"'>"+p2Team[i][1]+"</span> // Body: "+p2Team[i][5]+"/"+p2Team[i][4]+" // Mind: "+p2Team[i][7]+"/"+p2Team[i][6]+"<br>";
                        break;
                }

                document.getElementById("battleOptions").innerHTML = txt;
                document.getElementById("nChr"+characterTurn).classList.add("highlight");
            }

            function getSkillType(skillId) {
                if(skillList[skillId][3] == "attack_single" || skillList[skillId][3] == "status_target") return true;
                else return false;
            }

            function showAttacks(state, action) {
                menu = document.getElementById("battleMenu");
                options = document.getElementById("battleOptions");

                switch(state) {
                    case 0:
                        menu.innerHTML = "<span class=\"btlBtn\" onclick=\"showAttacks(1, 'A')\">Attack</span><br><span class=\"btlBtn\" onclick=\"showAttacks(2, 'S')\">Skills</span><br><span class=\"btlBtn\" onclick=\"prepareTurn('G')\">Guard</span><br><span class=\"btlBtn\" onclick=\"lose()\">Give Up</span><br>";
                        action = "";
                        showContestants();
                        break;

                    case 1:
                        menu.innerHTML = "<span class=\"btlBtn\" onclick=\"showAttacks(0, null)\">Cancel</span><br>";
                        options.innerHTML = "";
                        switch(playerSide) {
                            case 0:
                                for(var i = teamSize-1; i >= 0; i--) {
                                    if(p2Team[i] != "null") options.innerHTML += "<span id='"+i+"' class='btlBtn' onclick=\"prepareTurn('"+(action+"_"+i)+"')\">"+p2Team[i][1]+"</span> // Body: "+p2Team[i][5]+" <br>";
                                }       
                                break;

                            default:                       
                                for(var i = teamSize-1; i >= 0; i--) {
                                    if(p1Team[i] != "null") options.innerHTML += "<span id='"+i+"' class='btlBtn' onclick=\"prepareTurn('"+(action+"_"+i)+"')\">"+p1Team[i][1]+"</span> // Body: "+p1Team[i][5]+" <br>";
                                }
                                break;
                        }
                        break;

                    case 2:
                        menu.innerHTML = "<span class=\"btlBtn\" onclick=\"showAttacks(0, null)\">Cancel</span><br>";
                        options.innerHTML = "";
                        switch(playerSide) {
                            case 0:
                                for(var i = 0; i < p1Team[characterTurn][2].length; i++) {
                                    if(p1Team[characterTurn][2][i] && getSkillType(characterList[p1Team[characterTurn][0]-1][i+5]-1)) options.innerHTML += "<span id=\"" + i + "\" class=\"btlBtn\" onclick=\"showAttacks(1, '"+(action+i)+"')\">" + skillList[characterList[p1Team[characterTurn][0]-1][i+5]-1][1] +"</span> // "+skillList[characterList[p1Team[characterTurn][0]-1][i+5]-1][5]+"<br>";
                                    else if(p1Team[characterTurn][2][i]) options.innerHTML += "<span id=\"" + i + "\" class=\"btlBtn\" onclick=\"prepareTurn('"+(action+i)+"')\">" + skillList[characterList[p1Team[characterTurn][0]-1][i+5]-1][1] +"</span> // "+skillList[characterList[p1Team[characterTurn][0]-1][i+5]-1][5]+"<br>";
                                }      
                                break;

                            default:                       
                                for(var i = 0; i < p2Team[characterTurn][2].length; i++) {
                                    if(p2Team[characterTurn][2][i] && getSkillType(characterList[p2Team[characterTurn][0]-1][i+5]-1)) options.innerHTML += "<span id=\"" + i + "\" class=\"btlBtn\" onclick=\"showAttacks(1, '"+(action+i)+"')\">" + skillList[characterList[p2Team[characterTurn][0]-1][i+5]-1][1] +"</span> // "+skillList[characterList[p2Team[characterTurn][0]-1][i+5]-1][5]+"<br>";
                                    else if(p2Team[characterTurn][2][i]) options.innerHTML += "<span id=\"" + i + "\" class=\"btlBtn\" onclick=\"prepareTurn('"+(action+i)+"')\">" + skillList[characterList[p2Team[characterTurn][0]-1][i+5]-1][1] +"</span> // "+skillList[characterList[p2Team[characterTurn][0]-1][i+5]-1][5]+"<br>";
                                } 
                                break;
                        }
                        break;

                    case 3:
                        menu.innerHTML = "<span class=\"btlBtn\" onclick=\"lose()\">Give Up</span><br>";
                        break;

                    default:
                    case 4:
                        menu.innerHTML = "...";
                        break;

                    case 5:
                        menu.innerHTML = "<span class=\"btlBtn\" onclick=\"toMenu()\">Home</span><br>";
                        break;
                }
                
            }

            function resetAnimations() {
                for(var i = 0; i < teamSize; i++) {
                    animator(("p"+i), "none.png");
                    animator(("e"+i), "none.png");
                    notifier(("p"+i), "");
                    notifier(("e"+i), "");
                }
            }

            async function animator(chr, anim) {;
                if(chr[0] == "p") {
                    document.getElementById("pAnim"+(parseInt(chr[1])+1)).src = "media/img/sprites/animations/"+anim;
                }
                else {
                    document.getElementById("eAnim"+(parseInt(chr[1])+1)).src = "media/img/sprites/animations/"+anim;
                }
            }

            async function notifier(chr, value) {
                if(chr[0] == "p") {
                    document.getElementById("pNotif"+(parseInt(chr[1])+1)).innerHTML = value;
                }
                else {
                    document.getElementById("eNotif"+(parseInt(chr[1])+1)).innerHTML = value;
                }
            }

            async function writer(txt) {
                document.getElementById("battleOptions").innerHTML = txt;
            }

            async function playSound(sound) {
                if(sound != null) { var sfx = new Audio("media/snd/sfx/"+sound); sfx.play(); }
            }

            async function playMusic(song) {
                if(song != null) { var bgm = new Audio("media/snd/bgm/"+song); bgm.loop = true; bgm.volume = 0.25; bgm.play(); }
            }

            function prepareTurn(action) {
                if(characterTurn!=3) showAttacks(0, null);
                playerTurn += characterTurn + "_" + action;
                switch(playerSide) {
                    case 0:
                        do { characterTurn++; playerTurn += " "; } while((p1Team[characterTurn] == "null") && (characterTurn != 4));
                        break;

                    default:                       
                        do { characterTurn++; playerTurn += " "; } while((p2Team[characterTurn] == "null") && (characterTurn != 4));
                        break;
                
                }
                if(characterTurn == 4) finishTurn();
                else showContestants();
            }

            var turnCheck = null;
            var idleTimerW = null;
            async function startTurnCheck() {
                turnCheck = setInterval(checkTurn, 3000);
                idleTimerW = setInterval(idleWin, 120000);
                document.getElementById("battleOptions").innerHTML = "Waiting for Opponent...";
                showAttacks(3, null);
            }

            async function checkTurn() {
                $.ajax({
                    type :'POST',
                    data :
                        {
                            turn: prevTurn
                        },
                    url  : 'check_turn.php', 
                    dataType : 'json',
                    encode : true,
                    success : function(data){
                        if(data[0] == 1) {
                            executeTurn(data[1], data[2]);
                        }
                    },
                    error : function(e){
                        console.log(e);
                    }
                });
            }

            async function finishTurn() {
                $.ajax({
                    type :'POST',
                    data :
                        {
                            turn: prevTurn,
                            side: playerSide,
                            actions: playerTurn,
                            lobby: lobbyId
                        },
                    url  : 'finish_turn.php', 
                    dataType : 'json',
                    encode : true,
                    success : function(data){
                        prevTurn = data;
                        document.getElementById("battleOptions").innerHTML = "Waiting for Opponent...";
                        showAttacks(3, null);
                        startTurnCheck();

                    },
                    error : function(e){
                        console.log(e);
                    }
                });
            }

            var turnOrder = [];
            function executeTurn(p1Turn, p2Turn) {
                clearInterval(idleTimerW);
                clearInterval(turnCheck);
                document.getElementById("battleOptions").innerHTML = "!!!";
                showAttacks(4, null);
                characterTurn = 0;
                p1Turn = p1Turn.split(' ');
                p2Turn = p2Turn.split(' ');

                for(var i = 0; i < teamSize; i++) {
                    if(p1Team[i] != "null") {
                        if(p1Turn[i][2] == "G") turnOrder.push([99,0,p1Turn[i]]);
                        else turnOrder.push([p1Team[i][12],0,p1Turn[i]]);
                    }
                    if(p2Team[i] != "null"){
                        if(p2Turn[i][2] == "G") turnOrder.push([99,1,p2Turn[i]]);
                        else turnOrder.push([p2Team[i][12],1,p2Turn[i]]);
                    }   
                }
                turnOrder.sort(function(a, b){return b[0]-a[0]});
                var animOrder = [];
                var msgOrder = [];
                var notifOrder = [];
                var extraOrder = [];
                var deathOrder = [];

                for(i = 0; i < teamSize; i++) {
                    if(p1Team[i] != "null") p1Team[i][13] = false;
                    if(p2Team[i] != "null") p2Team[i][13] = false;
                }
                
                //console.log(turnOrder);
                // the big juice
                for(var i = 0; i < turnOrder.length; i++) {
                    var myTurn = (turnOrder[i][1] == playerSide);
                    var attackerId = parseInt(turnOrder[i][2][0]);
                    var targets = [];
                    switch(turnOrder[i][2][2]) {
                        case 'A': {
                            var atkAnim, damage, guard;
                            var enemyId = parseInt(turnOrder[i][2][turnOrder[i][2].length-1]);

                            if(turnOrder[i][1] == 0) {
                                if(p2Team[enemyId][13]) guard = 3; else guard = 1;
                                damage = parseInt(((100*(p1Team[attackerId][8]/p2Team[enemyId][9]))/5)/guard);
                                if(damage < 10) damage = 10;
                                p2Team[enemyId][5] -= damage;
                                atkAnim = p1Team[attackerId][3];
                                msgOrder.push(p1Team[attackerId][1]+" attacked "+p2Team[enemyId][1]+" for "+damage+" damage!");
                            }
                            else {
                                if(p1Team[enemyId][13]) guard = 3; else guard = 1;
                                damage = parseInt(((100*(p2Team[attackerId][8]/p1Team[enemyId][9]))/5)/guard);
                                if(damage < 10) damage = 10;
                                p1Team[enemyId][5] -= damage;
                                atkAnim = p2Team[attackerId][3];
                                msgOrder.push(p2Team[attackerId][1]+" attacked "+p1Team[enemyId][1]+" for "+damage+" damage!");
                            }

                            if(myTurn) targets[0] = ("e"+enemyId);
                            else targets[0] = ("p"+enemyId);
                            animOrder.push([targets,atkAnim+".gif",atkAnim+".ogg"]);
                            notifOrder.push([targets,damage]);
                            break;
                        }

                        case 'S': {
                            skillId = parseInt(turnOrder[i][2][3]);
                            if(turnOrder[i][1] == 0) {
                                skillName = skillList[characterList[p1Team[attackerId][0]-1][skillId+5]-1][1];
                                skillType = skillList[characterList[p1Team[attackerId][0]-1][skillId+5]-1][3];
                                skillPower = parseInt(skillList[characterList[p1Team[attackerId][0]-1][skillId+5]-1][4]);
                                skillCost = parseInt(skillList[characterList[p1Team[attackerId][0]-1][skillId+5]-1][5]);
                                skillExtra = skillList[characterList[p1Team[attackerId][0]-1][skillId+5]-1][6];
                                skillAnim = skillList[characterList[p1Team[attackerId][0]-1][skillId+5]-1][8];
                            }
                            else {
                                skillName = skillList[characterList[p2Team[attackerId][0]-1][skillId+5]-1][1];
                                skillType = skillList[characterList[p2Team[attackerId][0]-1][skillId+5]-1][3];
                                skillPower = parseInt(skillList[characterList[p2Team[attackerId][0]-1][skillId+5]-1][4]);
                                skillCost = parseInt(skillList[characterList[p2Team[attackerId][0]-1][skillId+5]-1][5]);
                                skillExtra = skillList[characterList[p2Team[attackerId][0]-1][skillId+5]-1][6];
                                skillAnim = skillList[characterList[p2Team[attackerId][0]-1][skillId+5]-1][8];
                            }
                            if(skillExtra != "none;") {
                                skillExtra = skillExtra.split(';');
                                for(var j = 0; j < skillExtra.length-1; j++) {
                                    skillExtra[j] = skillExtra[j].split(' : ');
                                }
                            }

                            var canAfford = false;
                            if((turnOrder[i][1] == 0) && (p1Team[attackerId][7] >= skillCost)) { canAfford = true; p1Team[attackerId][7] -= skillCost; }
                            else if((turnOrder[i][1] == 1) && (skillCost <= p2Team[attackerId][7])) { canAfford = true; p2Team[attackerId][7] -= skillCost; }

                            if(canAfford) {
                                switch(skillType) {
                                    case 'attack_single': {
                                        var damage, guard;
                                        var enemyId = parseInt(turnOrder[i][2][turnOrder[i][2].length-1]);

                                        if(turnOrder[i][1] == 0) {
                                            if(p2Team[enemyId][13]) guard = 3; else guard = 1;
                                            damage = parseInt(((skillPower*(p1Team[attackerId][10]/p2Team[enemyId][11]))/5)/guard);
                                            if(damage < 10) damage = 10;
                                            p2Team[enemyId][5] -= damage;
                                            msgOrder.push(p1Team[attackerId][1]+" used "+skillName+" to attack "+p2Team[enemyId][1]+" for "+damage+" damage!");
                                            
                                        }
                                        else {
                                            if(p1Team[enemyId][13]) guard = 3; else guard = 1;
                                            damage = parseInt(((skillPower*(p2Team[attackerId][10]/p1Team[enemyId][11]))/5)/guard);
                                            if(damage < 10) damage = 10;
                                            p1Team[enemyId][5] -= damage;
                                            msgOrder
                                            .push(p2Team[attackerId][1]+" used "+skillName+" to attack "+p1Team[enemyId][1]+" for "+damage+" damage!");
                                        }

                                        if(skillExtra != "none;") {
                                            for(var j = 0; j < skillExtra.length-1; j++) {
                                                if(skillExtra[j][0] == "status_target") {
                                                    if(turnOrder[i][1] == 0) {
                                                        p2Team[enemyId][14].push(skillExtra[j][1]);
                                                        p2Team[enemyId][14] = [...new Set(p2Team[enemyId][14])];
                                                    }
                                                    else {
                                                        p1Team[enemyId][14].push(skillExtra[j][1]);
                                                        p1Team[enemyId][14] = [...new Set(p1Team[enemyId][14])];
                                                    }
                                                }
                                                else if(skillExtra[j][0] == "status_self") {
                                                    if(turnOrder[i][1] == 0) {
                                                        p1Team[attackerId][14].push(skillExtra[j][1]);
                                                        p1Team[attackerId][14] = [...new Set(p1Team[attackerId][14])];
                                                    }
                                                    else {
                                                        p2Team[attackerId][14].push(skillExtra[j][1]);
                                                        p2Team[attackerId][14] = [...new Set(p2Team[attackerId][14])];
                                                    }
                                                }
                                            }
                                        }

                                        if(myTurn) targets[0] = ("e"+enemyId);
                                        else targets[0] = ("p"+enemyId);
                                        animOrder.push([targets,skillAnim+".gif",skillAnim+".ogg"]);
                                        notifOrder.push([targets,damage]);
                                        break;
                                    }

                                    case 'attack_team': {
                                        var damage;
                                        var guard = 1;
                                        var mdef = 0;

                                        if(turnOrder[i][1] == 0) {
                                            for(var j = 0; j < teamSize; j++) { if(p2Team[j] != "null") { if(p2Team[j][13]) guard += 1; mdef += p2Team[j][11]; } }
                                            damage = parseInt(((skillPower*(p1Team[attackerId][10]/(mdef/4)))/5)/guard);
                                            if(damage < 10) damage = 10;
                                            for(var j = 0; j < teamSize; j++) { if(p2Team[j] != "null") { p2Team[j][5] -= damage; targets.push(j); } }
                                            msgOrder.push(p1Team[attackerId][1]+" used "+skillName+" to attack the enemy team for "+damage+" damage!");
                                            
                                        }
                                        else {
                                            for(var j = 0; j < teamSize; j++) { if(p1Team[j] != "null") { if(p1Team[j][13]) guard += 1; mdef += p1Team[j][11]; } }
                                            damage = parseInt(((skillPower*(p2Team[attackerId][10]/(mdef/4)))/5)/guard);
                                            if(damage < 10) damage = 10;
                                            for(var j = 0; j < teamSize; j++) { if(p1Team[j] != "null") { p1Team[j][5] -= damage; targets.push(j); } }
                                            msgOrder.push(p2Team[attackerId][1]+" used "+skillName+" to attack the enemy team for "+damage+" damage!");
                                        }

                                        if(skillExtra != "none;") {
                                            for(var j = 0; j < skillExtra.length-1; j++) {
                                                if(skillExtra[j][0] == "status_target") {
                                                    if(turnOrder[i][1] == 0) {
                                                        p2Team[enemyId][14].push(skillExtra[j][1]);
                                                        p2Team[enemyId][14] = [...new Set(p2Team[enemyId][14])];
                                                    }
                                                    else {
                                                        p1Team[enemyId][14].push(skillExtra[j][1]);
                                                        p1Team[enemyId][14] = [...new Set(p1Team[enemyId][14])];
                                                    }
                                                }
                                                else if(skillExtra[j][0] == "status_self") {
                                                    if(turnOrder[i][1] == 0) {
                                                        p1Team[attackerId][14].push(skillExtra[j][1]);
                                                        p1Team[attackerId][14] = [...new Set(p1Team[attackerId][14])];
                                                    }
                                                    else {
                                                        p2Team[attackerId][14].push(skillExtra[j][1]);
                                                        p2Team[attackerId][14] = [...new Set(p2Team[attackerId][14])];
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if(myTurn) { for(j = 0; j < targets.length; j++) targets[j] = "e"+targets[j]; }
                                        else { for(j = 0; j < targets.length; j++) targets[j] = "p"+targets[j]; }
                                        animOrder.push([targets,skillAnim+".gif",skillAnim+".ogg"]);
                                        notifOrder.push([targets,damage]);
                                        break;
                                    }

                                    case 'status_self': {
                                        if(skillExtra != "none;") {
                                            for(var j = 0; j < skillExtra.length-1; j++) {
                                                if(skillExtra[j][0] == "status_self") {
                                                    if(turnOrder[i][1] == 0) {
                                                        p1Team[attackerId][14].push(skillExtra[j][1]);
                                                        p1Team[attackerId][14] = [...new Set(p1Team[attackerId][14])];
                                                    }
                                                    else {
                                                        p2Team[attackerId][14].push(skillExtra[j][1]);
                                                        p2Team[attackerId][14] = [...new Set(p2Team[attackerId][14])];
                                                    }
                                                }
                                            }
                                        }

                                        if(turnOrder[i][1] == 0) msgOrder.push(p1Team[attackerId][1]+" used "+skillName+"!");
                                        else msgOrder.push(p2Team[attackerId][1]+" used "+skillName+"!"); 
                                        if(myTurn) targets[0] = ("p"+attackerId);
                                        else targets[0] = ("e"+attackerId);
                                        animOrder.push([targets,skillAnim+".gif",skillAnim+".ogg"]);
                                        notifOrder.push([targets,""]);
                                        break;
                                    }

                                    case 'status_target': {
                                        var enemyId = parseInt(turnOrder[i][2][turnOrder[i][2].length-1]);

                                        if(skillExtra != "none;") {
                                            for(var j = 0; j < skillExtra.length-1; j++) {
                                                if(skillExtra[j][0] == "status_target") {
                                                    if(turnOrder[i][1] == 0) {
                                                        p2Team[enemyId][14].push(skillExtra[j][1]);
                                                        p2Team[enemyId][14] = [...new Set(p2Team[enemyId][14])];
                                                    }
                                                    else {
                                                        p1Team[enemyId][14].push(skillExtra[j][1]);
                                                        p1Team[enemyId][14] = [...new Set(p1Team[enemyId][14])];
                                                    }
                                                }
                                            }
                                        }

                                        if(turnOrder[i][1] == 0) msgOrder.push(p1Team[attackerId][1]+" used "+skillName+" on "+p2Team[enemyId][1]+"!");
                                        else msgOrder.push(p2Team[attackerId][1]+" used "+skillName+" on "+p1Team[enemyId][1]+"!"); 
                                        if(myTurn) targets[0] = ("e"+enemyId);
                                        else targets[0] = ("p"+enemyId);
                                        animOrder.push([targets,skillAnim+".gif",skillAnim+".ogg"]);
                                        notifOrder.push([targets,""]);
                                        break;
                                    }

                                    case 'team_heal': {
                                        var heal;

                                        if(turnOrder[i][1] == 0) {
                                            heal = parseInt((p1Team[attackerId][10]*parseInt(skillExtra[0][1]))/100);
                                            if(heal < 10) heal = 10;
                                            for(var j = 0; j < teamSize; j++) {
                                                if(p1Team[j] != "null") {
                                                    p1Team[j][5] += heal;
                                                    targets.push(j);
                                                }
                                            }
                                            msgOrder.push(p1Team[attackerId][1]+" used "+skillName+" to heal their team for "+heal+" health!");
                                            
                                        }
                                        else {
                                            heal = parseInt((p2Team[attackerId][10]*parseInt(skillExtra[0][1]))/100);
                                            if(heal < 10) heal = 10;
                                            for(var j = 0; j < teamSize; j++) {
                                                if(p2Team[j] != "null") {
                                                    p2Team[j][5] += heal;
                                                    targets.push(j);
                                                }
                                            }
                                            msgOrder.push(p2Team[attackerId][1]+" used "+skillName+" to heal their team for "+heal+" health!");
                                        }


                                        if(myTurn) { for(j = 0; j < targets.length; j++) targets[j] = "p"+targets[j]; }
                                        else { for(j = 0; j < targets.length; j++) targets[j] = "e"+targets[j]; }
                                        animOrder.push([targets,skillAnim+".gif",skillAnim+".ogg"]);
                                        notifOrder.push([targets,heal]);
                                        break;
                                    }
                                }
                            }
                            else {
                                if(turnOrder[i][1] == 0) msgOrder.push(p1Team[attackerId][1]+"'s skill failed!");
                                else msgOrder.push(p2Team[attackerId][1]+"'s skill failed!");
                                if(myTurn) targets[0] = ("p"+attackerId);
                                else targets[0] = ("e"+attackerId);
                                animOrder.push([targets,"none.png",null]);
                                notifOrder.push([targets,""]);
                                break;
                               
                            }
                            break;
                        }

                        case 'G': {
                            if(turnOrder[i][1] == 0) { p1Team[attackerId][13] = true; msgOrder.push(p1Team[attackerId][1]+" protected themselves!"); }
                            else { p2Team[attackerId][13] = true; msgOrder.push(p2Team[attackerId][1]+" protected themselves!"); }
                            if(myTurn) targets[0] = ("p"+attackerId);
                            else targets[0] = ("e"+attackerId);
                            animOrder.push([targets,"guard.gif","guard.ogg"]);
                            notifOrder.push([targets,""]); 
                        }
                    }
                }

                for(var i = 0; i < teamSize; i++) {
                    if(p1Team[i] != "null") extraOrder.push([p1Team[i],0,i]);
                    if(p2Team[i] != "null") extraOrder.push([p2Team[i],1,i]);
                }

                var animOrderExtra = [];
                var msgOrderExtra = [];
                for(var i = 0; i < extraOrder.length; i++) {
                    var extraLength = extraOrder[i][0][14].length;
                    var spliceIndex = 0;
                    for(var j = 0; j < extraLength; j++) {
                        var targets = [];
                        var status = extraOrder[i][0][14][spliceIndex];
                        if(extraOrder[i][1] == playerSide) targets[0] = "p" + extraOrder[i][2];
                        else targets[0] = "e" + extraOrder[i][2];

                        switch(status) {
                            case 'focus':
                                extraOrder[i][0][8] = parseInt(parseInt(extraOrder[i][0][8])*2);
                                extraOrder[i][0][14].splice(spliceIndex, 1);
                                spliceIndex--; 
                                msgOrderExtra.push(extraOrder[i][0][1]+" concentrated deeply!");
                                animOrderExtra.push([targets,"status_generic.gif","status_generic.ogg"]);
                                break;
                            case 'vulnerable':
                                extraOrder[i][0][9] = parseInt(parseInt(extraOrder[i][0][9])/2);
                                extraOrder[i][0][11] = parseInt(parseInt(extraOrder[i][0][11])/2);
                                extraOrder[i][0][14].splice(spliceIndex, 1);
                                spliceIndex--; 
                                msgOrderExtra.push(extraOrder[i][0][1]+" became vulnerable to enemy attacks!");
                                animOrderExtra.push([targets,"status_generic.gif","status_generic.ogg"]);
                                break;
                            case 'concussion':
                                extraOrder[i][0][8] = parseInt(parseInt(extraOrder[i][0][8])*0.75);
                                extraOrder[i][0][10] = parseInt(parseInt(extraOrder[i][0][10])*0.75);
                                extraOrder[i][0][12] = parseInt(parseInt(extraOrder[i][0][12])*0.75);
                                extraOrder[i][0][14].splice(spliceIndex, 1);
                                spliceIndex--; 
                                msgOrderExtra.push(extraOrder[i][0][1]+" suffered from a concussion!");
                                animOrderExtra.push([targets,"status_generic.gif","status_generic.ogg"]);
                                break;
                            case 'danse':
                                extraOrder[i][0][10] = parseInt(parseInt(extraOrder[i][0][10])*2);
                                extraOrder[i][0][14].splice(spliceIndex, 1);
                                spliceIndex--; 
                                msgOrderExtra.push(extraOrder[i][0][1]+" perfomed a strange ritual...");
                                animOrderExtra.push([targets,"whispers_evil.gif","whispers.ogg"]);
                                break;
                            case 'burning':
                                extraOrder[i][0][5] -= parseInt(parseInt(extraOrder[i][0][5])*0.2);
                                msgOrderExtra.push(extraOrder[i][0][1]+" was hurt by the flames around them!");
                                animOrderExtra.push([targets,"burn.gif","burn.ogg"]);
                                break;
                            case 'attack_up':
                                extraOrder[i][0][8] = parseInt(parseInt(extraOrder[i][0][8])*1.5);
                                extraOrder[i][0][14].splice(spliceIndex, 1);
                                spliceIndex--; 
                                msgOrderExtra.push(extraOrder[i][0][1]+" increased their strength!");;
                                animOrderExtra.push([targets,"status_generic.gif","status_generic.ogg"]);
                                break;
                            case 'mattack_up':
                                extraOrder[i][0][10] = parseInt(parseInt(extraOrder[i][0][10])*1.5);
                                extraOrder[i][0][14].splice(spliceIndex, 1);
                                spliceIndex--; 
                                msgOrderExtra.push(extraOrder[i][0][1]+" increased their magic!");
                                animOrderExtra.push([targets,"status_generic.gif","status_generic.ogg"]);
                                break;
                            case 'mattack_down':
                                extraOrder[i][0][10] = parseInt(parseInt(extraOrder[i][0][10])*0.75);
                                extraOrder[i][0][14].splice(spliceIndex, 1);
                                spliceIndex--; 
                                msgOrderExtra.push(extraOrder[i][0][1]+"'s magic was weakned!");
                                animOrderExtra.push([targets,"status_generic.gif","status_generic.ogg"]);
                                break;
                        }
                        spliceIndex++;
                    }
                }

                for(var i = 0; i < teamSize; i++) {
                    if(p1Team[i] != "null") {
                        if(p1Team[i][5] > p1Team[i][4]) p1Team[i][5] = p1Team[i][4];
                        if(p1Team[i][5] <= 0) { 
                            if(playerSide == 0) deathOrder.push(["p"+i,p1Team[i][1]]); 
                            else deathOrder.push(["e"+i,p1Team[i][1]]);
                            p1Team[i] = "null";
                        }
                    }
                    if(p2Team[i] != "null") {
                        if(p2Team[i][5] > p2Team[i][4]) p2Team[i][5] = p2Team[i][4];
                        if(p2Team[i][5] <= 0) { 
                            if(playerSide == 0) deathOrder.push(["e"+i,p2Team[i][1]]); 
                            else deathOrder.push(["p"+i,p2Team[i][1]]);
                            p2Team[i] = "null";
                        }
                    }
                }
                
                //console.log(animOrder);
                var loops = 0;
                var xloops = 0;
                var dloops = 0;
                var animTimer = setInterval( function() {
                        if(loops == animOrder.length) { clearTimeout(animTimer); animTimer = setInterval( function() {
                            if(xloops == animOrderExtra.length) { clearTimeout(animTimer); animTimer = setInterval( function() {
                                if(dloops == deathOrder.length) { clearTimeout(animTimer); newTurn(); }
                                else {
                                    resetAnimations();
                                    if(dloops == 0) {
                                        for(var i = 0; i < deathOrder.length; i++) animator(deathOrder[i][0], "death.gif"); 
                                    }
                                    writer(deathOrder[dloops][1] + " died!");
                                    playSound("death.ogg");
                                    loadContestants();
                                    dloops++
                                }
                            }, 1500); }
                            else {
                                resetAnimations();
                                for(var i = 0; i < animOrderExtra[xloops][0].length; i++) {
                                    animator(animOrderExtra[xloops][0][i], animOrderExtra[xloops][1]);
                                }
                                playSound(animOrderExtra[xloops][2]);
                                writer(msgOrderExtra[xloops]);
                                xloops++;
                            }
                        }, 1500); }
                        else {
                            resetAnimations();
                            //console.log(animOrder[loops]);
                            for(var i = 0; i < animOrder[loops][0].length; i++) {
                                animator(animOrder[loops][0][i], animOrder[loops][1]);
                                notifier(notifOrder[loops][0][i], notifOrder[loops][1]);
                            }
                            playSound(animOrder[loops][2]);
                            writer(msgOrder[loops]);
                            loops++;
                        }
                    }, 1500);
            }

            function newTurn() {
                characterTurn = 0;
                var living = -1;
                var dead = 0;
                var edead = 0;
                for(var i = 0; i < teamSize; i++) {
                    if(playerSide == 0) {
                        if(p1Team[i] == "null") dead++;
                        else if(living == -1) living = i;
                        if(p2Team[i] == "null") edead++;
                    }
                    else {
                        if(p2Team[i] == "null") dead++;
                        else if(living == -1) living = i;
                        if(p1Team[i] == "null") edead++;
                    }
                }

                if((dead == 4) && (edead == 4)) massacre();
                else if(dead == 4) lose();
                else if(edead == 4) win();
                else {
                    characterTurn = living;
                    playerTurn = "";
                    turnOrder = [];
                    resetAnimations();
                    showAttacks(0, null); 
                }
            }

            function win() {
                showAttacks(5, null);

                var p1LocalId = null;
                var p2LocalId = null;

                for(var i = 0; i < userList.length; i++) {
                    if(userList[i][0] == p1Id) p1LocalId = i;
                    if(userList[i][0] == p2Id) p2LocalId = i;
                }

                var p1Name = userList[p1LocalId][1];
                var p2Name = userList[p2LocalId][1];

                if(playerSide == 0) document.getElementById("battleOptions").innerHTML = p1Name+" won the battle!";
                else document.getElementById("battleOptions").innerHTML = p2Name+" won the battle!";
            }

            function lose() {
                showAttacks(5, null);

                var p1LocalId = null;
                var p2LocalId = null;

                for(var i = 0; i < userList.length; i++) {
                    if(userList[i][0] == p1Id) p1LocalId = i;
                    if(userList[i][0] == p2Id) p2LocalId = i;
                }

                var p1Name = userList[p1LocalId][1];
                var p2Name = userList[p2LocalId][1];

                if(playerSide == 0) document.getElementById("battleOptions").innerHTML = "You lost...<br>"+p2Name+" was victorious!";
                else document.getElementById("battleOptions").innerHTML = "You lost...<br>"+p1Name+" was victorious!";
            }

            function massacre() {
                showAttacks(5, null);
                document.getElementById("battleOptions").innerHTML = "The battle ended in a massacre, and there were no victors...";
            }

            async function idleWin() {
                clearInterval(idleTimerW);
                showAttacks(5, null);
                document.getElementById("battleOptions").innerHTML = "The opponent was too cowardly to move, thus they surrendered...";
            }

            function toMenu() {
                cancelSearch();
                window.top.location.reload();
            }
        </script>
    </head>
    <body>
        <div id="lobby">
            <section id="lobbyLayout">
                <div class="teamBox">
                    <div id="playBox">
                        <div class="labelBox">Pick your Team:</div>
                        <div id="teamSelectHolder"></div>
                        <span id="teamPrice"></span>
                        <br><br>
                        <div id="buttonHolder">
                            <button id="playButton" onclick="beginSearch()"><span class="btnTitle">Battle!</span><br>Search for a random contestant</button>
                            <br><br>
                            <button id="cancelButton" class="hidden" onclick="cancelSearch()"><span class="btnTitle">Cancel</span></button>
                        </div>
                    </div>
                </div>
                <div class="formatBox"> 
                    <div id="formatsBox">
                        <div class="labelBox">Pick a Format:</div>
                        <div id="formatHolder">
                            <select id="formatSelect">
                                <option value="100">Villager (100$)</option>
                                <option value="500">Wealthsman (500$)</option>
                                <option value="1000">Guest of Honor (1000$)</option>
                                <option value="9999">Mayor of Prehevil (9999$)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="newsBox">
                   <button id="btn_return" type="button" onclick="mainMenu()"> X Main Menu</button>
                </div>
            </section>
        </div>
        <div id="battle" class="hidden">
            <section id="battleLayout">
                <div id="playerSide" class="playerSide">
                    Contestant 1
                </div>
                <div id="battleStage" class="battleStage"> 
                    <div id="playerBattleSide">
                        <div class="playerBox">
                            <img id="pCont1" class="playerC" src='https://i.postimg.cc/HsbpN1my/back-none.png' style='max-height: 180px; max-width: 180px;'>
                            <img id="pAnim1" class="playerA" src='media/img/sprites/animations/none.png' style='max-height: 180px; max-width: 180px;'>
                            <div id="pNotif1" class="playerN" style='max-height: 180px; max-width: 180px;'></div>
                         </div>
                         <div class="playerBox">
                            <img id="pCont2" class="playerC" src='https://i.postimg.cc/HsbpN1my/back-none.png' style='max-height: 180px; max-width: 180px;'>
                            <img id="pAnim2" class="playerA" src='media/img/sprites/animations/none.png' style='max-height: 180px; max-width: 180px;'>
                            <div id="pNotif2" class="playerN" style='max-height: 180px; max-width: 180px;'></div>
                         </div>
                         <div class="playerBox">
                            <img id="pCont3" class="playerC" src='https://i.postimg.cc/HsbpN1my/back-none.png' style='max-height: 180px; max-width: 180px;'>
                            <img id="pAnim3" class="playerA" src='media/img/sprites/animations/none.png' style='max-height: 180px; max-width: 180px;'>
                            <div id="pNotif3" class="playerN" style='max-height: 180px; max-width: 180px;'></div>
                         </div>
                         <div class="playerBox">
                            <img id="pCont4" class="playerC" src='https://i.postimg.cc/HsbpN1my/back-none.png' style='max-height: 180px; max-width: 180px;'>
                            <img id="pAnim4" class="playerA" src='media/img/sprites/animations/none.png' style='max-height: 180px; max-width: 180px;'>
                            <div id="pNotif4" class="playerN" style='max-height: 180px; max-width: 180px;'></div>
                         </div>
                    </div>
                    <div id="enemyBattleSide">
                        <div class="enemyBox">
                            <img id="eCont1" class="enemyC" src='https://i.postimg.cc/2SzfXqTs/front-none.png' style='max-height: 180px; max-width: 180px;'>
                            <img id="eAnim1" class="enemyA" src='media/img/sprites/animations/none.png' style='max-height: 180px; max-width: 180px;'>
                            <div id="eNotif1" class="enemyN" style='max-height: 180px; max-width: 180px;'></div>
                         </div>
                         <div class="enemyBox">
                            <img id="eCont2" class="enemyC" src='https://i.postimg.cc/2SzfXqTs/front-none.png' style='max-height: 180px; max-width: 180px;'>
                            <img id="eAnim2" class="enemyA" src='media/img/sprites/animations/none.png' style='max-height: 180px; max-width: 180px;'>
                            <div id="eNotif2" class="enemyN" style='max-height: 180px; max-width: 180px;'></div>
                         </div>
                         <div class="enemyBox">
                            <img id="eCont3" class="enemyC" src='https://i.postimg.cc/2SzfXqTs/front-none.png' style='max-height: 180px; max-width: 180px;'>
                            <img id="eAnim3" class="enemyA" src='media/img/sprites/animations/none.png' style='max-height: 180px; max-width: 180px;'>
                            <div id="eNotif3" class="enemyN" style='max-height: 180px; max-width: 180px;'></div>
                         </div>
                         <div class="enemyBox">
                            <img id="eCont4" class="enemyC" src='https://i.postimg.cc/2SzfXqTs/front-none.png' style='max-height: 180px; max-width: 180px;'>
                            <img id="eAnim4" class="enemyA" src='media/img/sprites/animations/none.png' style='max-height: 180px; max-width: 180px;'>
                            <div id="eNotif4" class="enemyN" style='max-height: 180px; max-width: 180px;'></div>
                         </div>
                    </div>
                </div>
                <div id="enemySide" class="enemySide">
                    Contestant 2
                </div>
                <div id="battleMenu" class="battleMenu">
                    <span class="btlBtn" onclick="showAttacks(1, 'A')">Attack</span><br>
                    <span class="btlBtn" onclick="showAttacks(2, 'S')">Skills</span><br>
                    <span class="btlBtn" onclick="prepareTurn('G')">Guard</span><br>
                    <span class="btlBtn" onclick="lose()">Give Up</span><br>
                </div>
                <div id="battleOptions" class="battleOptions">
                    Party Members
                </div>
            </section>
        </div>
    </body>
    <script>
        createTeamSelect();
    </script>
</html>