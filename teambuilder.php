<?php
    session_start();
     
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
        echo "<script> alert('You\'re not logged in!\\nLogin from the Homepage to access the Teambuilder.'); </script>";
        echo "<script> parent.open_page('home.php') </script>";
        exit;
    }

    require_once 'connect.php';
    $conn->query("USE dataset");

    $characters = $conn->query("SELECT * FROM Contestants")->fetchAll();
    $skills = $conn->query("SELECT * FROM Skills")->fetchAll();
    $weapons = $conn->query("SELECT * FROM Weapons")->fetchAll();
    $headarmor = $conn->query("SELECT * FROM Headarmor")->fetchAll();
    $bodyarmor = $conn->query("SELECT * FROM Bodyarmor")->fetchAll();
    $acessory = $conn->query("SELECT * FROM Acessory")->fetchAll();

    $conn->query("USE playerset");

    //function

    if(!empty($_POST)) {
        switch ($_POST["submitAction"]) {
            case "saveTeams":
                $teamdata = json_decode($_POST['teamData'], true);
                
                foreach($teamdata as $data) {
                    $sql = "UPDATE Teams SET TeamName = :teamname, Chr1 = :chr1, Chr2 = :chr2, Chr3 = :chr3, Chr4 = :chr4 WHERE UserId = :userid AND TeamId = :teamid";

                    if($stmt = $conn->prepare($sql)) {
                        $param_uid = $_SESSION["id"];
                        $param_tid = $data[0];
                        $param_tname = $data[2];
                        $param_chr1 = $data[3];
                        $param_chr2 = $data[4];
                        $param_chr3 = $data[5];
                        $param_chr4 = $data[6];

                        $stmt->bindParam(":userid", $param_uid, PDO::PARAM_STR);
                        $stmt->bindParam(":teamid", $param_tid, PDO::PARAM_STR);
                        $stmt->bindParam(":teamname", $param_tname, PDO::PARAM_STR);
                        $stmt->bindParam(":chr1", $param_chr1, PDO::PARAM_STR);
                        $stmt->bindParam(":chr2", $param_chr2, PDO::PARAM_STR);
                        $stmt->bindParam(":chr3", $param_chr3, PDO::PARAM_STR);
                        $stmt->bindParam(":chr4", $param_chr4, PDO::PARAM_STR);

                        $stmt->execute();
                    }
                }
                echo "<script> alert('Teams saved sucessfully!'); </script>";
                break;

            case "newTeam":
                $sql = "SELECT COUNT(*) FROM Teams WHERE UserId = :id";

                if($stmt = $conn->prepare($sql)) {
                    $stmt->bindParam(":id", $param_id, PDO::PARAM_STR);
                    
                    $param_id = $_SESSION["id"];
                }
                $stmt->execute();
                $teamcount = $stmt->fetchColumn();

                if($teamcount < 10) {
                    $sql = "INSERT INTO Teams (UserId) VALUES (:id)";

                    if($stmt = $conn->prepare($sql)) {
                        $param_id = $_SESSION["id"];

                        $stmt->bindParam(":id", $param_id, PDO::PARAM_STR);

                        $stmt->execute();
                        echo "<script> alert('New Team created sucessfully!'); </script>";
                    }
                }
                else {
                    echo "<script> alert('You already have the max of 10 Teams!\\nDelete a Team before creating a new one.'); </script>";
                } 
                break;
            case "deleteTeam":
                $sql = "DELETE FROM Teams WHERE UserId = :userid AND TeamId = :teamid";
                if($stmt = $conn->prepare($sql)) {
                    $param_uid = $_SESSION["id"];
                    $param_tid = $_POST["teamToDelete"];

                    $stmt->bindParam(":userid", $param_uid, PDO::PARAM_STR);
                    $stmt->bindParam(":teamid", $param_tid, PDO::PARAM_STR);

                    $stmt->execute();
                }
                echo "<script> alert('Team deleted sucessfully!'); </script>";
                break;
            default:
                echo "<script> alert('Oops! Something went wrong. Please try again later.'); </script>";
                break;
        }
        
    }

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
        <title>Teambuilder</title>
        <link rel="stylesheet" href="styles/teambuilder/builder.css">
        <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            var characterList = <?php echo json_encode($characters); ?>;
            var skillList = <?php echo json_encode($skills); ?>;
            var weaponList = <?php echo json_encode($weapons); ?>;
            var headarmorList = <?php echo json_encode($headarmor); ?>;
            var bodyarmorList = <?php echo json_encode($bodyarmor); ?>;
            var acessoryList = <?php echo json_encode($acessory); ?>;
            var teamList = <?php echo json_encode($teamlist); ?>;

            const teamSize = 4;
            var currentTeams = teamList.length;
            var editingTeam;
            var characterSlot;
            var teamPrice = [];

            /*
                TEAM LIST FUNCTIONS !!!
            */

            function mainMenu() {
                if (confirm("Are you sure you wish to exit?\nAny unsaved changes will be lost.")) {
                    parent.open_page('home.php');
                }
            }

            function loadTeamList() {
                var holder = document.getElementById('teamListHolder');
                var chrIcon = "";

                while(holder.firstChild) { 
                    holder.removeChild(holder.firstChild); 
                }

                holder.innerHTML += "Remember to click \"Save Teams\" before Creating or Deleting a Team!<br><br>";
                for(var i = 0; i < currentTeams; i++) {
                    var team = document.createElement("button");
                    team.type = "button";
                    team.value = i;
                    if(teamList[i][2] != null) team.innerHTML += teamList[i][2] + "<br>";
                    else team.innerHTML += "Untitled " + (i+1) + "<br>";
                    team.onclick = function() {openTeamBuilder(this.value); swapState(1); loadTeam();};

                    for (var j = 0; j < teamSize; j++) {
                        if(teamList[i][j+3] != null) chrIcon = " <img src='" + characterList[teamList[i][j+3][0]-1][3] +"' height=140px width=140px> ";
                        else chrIcon = " <img src='https://i.postimg.cc/rsQnj4Zx/portrait-none.png' height=140px width=140px> ";
                        team.innerHTML += chrIcon;
                    }

                    var delteam = document.createElement("button");
                    delteam.type = "button";
                    delteam.value = teamList[i][0];
                    delteam.innerHTML += "Delete Team";
                    delteam.onclick = function() {deleteTeam(this.value);};

                    var nxtln = document.createElement("br");
                    var nxtln2 = document.createElement("br");

                    holder.appendChild(team);
                    holder.appendChild(delteam);
                    holder.appendChild(nxtln);
                    holder.appendChild(nxtln2);
                }
            }

            function deleteTeam(teamId) {
                document.getElementById('teamToDelete').value = teamId;
                $("#deleteTeam").click();
            }

            function openTeamBuilder(team) {
                editingTeam = teamList[team];
            }

            function loadTeam() {
                var holder = document.getElementById('teamHolder');
                var chrName = "";
                var chrIcon = "";
                var chrPrice = "";

                while(holder.firstChild) { 
                    holder.removeChild(holder.firstChild); 
                }

                if(editingTeam[2] != null) document.getElementById('teamRename').value = editingTeam[2];
                else document.getElementById('teamRename').value = "Untitled Team";

                for (var i = 0; i < teamSize; i++) {
                    var button = document.createElement("button");
                    button.type = "button";
                    button.value = i;
                    chrName = "Add Member";
                    if(editingTeam[i+3] != null) chrName = editingTeam[i+3].substring(editingTeam[i+3].indexOf("(")+1, editingTeam[i+3].indexOf(")"));
                    if(chrName == "") chrName = characterList[editingTeam[i+3][0]-1][2];
                    if(editingTeam[i+3] != null) chrIcon = "<img src='" + characterList[editingTeam[i+3][0]-1][3] +"' height: 70px width: 70px>"
                    else chrIcon = "<img src='https://i.postimg.cc/rsQnj4Zx/portrait-none.png' height: 70px width: 70px>";
                    chrPrice = pricePreview(i);
                    button.innerHTML += chrName + "<br>" + chrIcon + "<br><br>" + chrPrice;
                    button.onclick = function() {swapState(2); loadCharacter(this.value);};

                    holder.appendChild(button);
                }

                var span = document.createElement("span");
                span.innerHTML += "<br><br>&nbsp Total Team Price: " + (teamPrice[0]+teamPrice[1]+teamPrice[2]+teamPrice[3]) + " $";
                holder.appendChild(span);

            }

            function renameTeam() {
                editingTeam[2] = document.getElementById('teamRename').value;
                document.getElementById('teamData').value = JSON.stringify(teamList);
            }

            function loadCharacter(teamSlot) {
                characterSlot = teamSlot;
                if(editingTeam[parseInt(characterSlot)+3] != null) {
                    document.getElementById("exportText").value = editingTeam[parseInt(characterSlot)+3];
                    importCharacter();
                    document.getElementById("exportText").value = "";
                }
                else {
                    document.getElementById("characterSelect").value = "none";
                    resetAttributes();
                    document.getElementById("exportText").value = "";
                }
            }

            /*
                TEAM BUILDER FUNCTIONS !!!
            */

            function pricePreview(teamSlot) {
                characterSlot = teamSlot;
                if(editingTeam[parseInt(characterSlot)+3] != null) {
                    var importText = editingTeam[parseInt(characterSlot)+3];
                    var importArray = importText.split('\n');
                    var character = importArray[0][0];
                    var skillCount = characterList[character-1][4];

                    var characterPrice = characterList[character-1][18];
                    var skillPrice = 0;
                    var equipmentPrice = 0;
                    var totalPrice = 0;

                    for (var i = 0; i < skillCount; i++) {
                        if(importArray[i+1][importArray[i+1].length-2] == 'u') skillPrice += skillList[(characterList[character-1][i+5])-1][7];
                    }

                    i++;
                    var weapon = importArray[i][importArray[i].length-1];
                    if(weapon != '0') equipmentPrice += weaponList[weapon-1][5];

                    i++;
                    var headarmor = importArray[i][importArray[i].length-1];
                    if(headarmor != '0') equipmentPrice += headarmorList[headarmor-1][9];

                    i++;
                    var bodyarmor = importArray[i][importArray[i].length-1];
                    if(bodyarmor != '0') equipmentPrice += bodyarmorList[bodyarmor-1][9];

                    i++;
                    var acessory = importArray[i][importArray[i].length-1];
                    if(acessory != '0') equipmentPrice += acessoryList[acessory-1][9];

                    totalPrice = ((characterPrice/10)*(skillPrice + equipmentPrice))/4 + characterPrice;

                    teamPrice[teamSlot] = totalPrice;
                    return totalPrice + " $";
                }
                else {
                    teamPrice[teamSlot] = 0;
                    return "0 $";
                }
            }

            /*
                CHARACTER BUILDER FUNCTIONS !!!
            */

            function createCharacterSelect() {
                var holder = document.getElementById('characterSelectHolder');
                var selectList = document.createElement("select");
                selectList.id = "characterSelect";
                holder.appendChild(selectList);

                var option = document.createElement("option");
                option.value = "none";
                option.text = "-";
                selectList.appendChild(option);
                for (var i = 0; i < characterList.length; i++) {
                    var option = document.createElement("option");
                    option.value = characterList[i][0];
                    option.text = characterList[i][2];
                    selectList.appendChild(option);
                }

                document.getElementById("characterSelect").onchange = function() {enableCustomization();};
            }

            function createWeaponSelect() {
                var holder = document.getElementById('weaponSelectHolder');
                var selectList = document.createElement("select");
                selectList.id = "weaponSelect";
                holder.appendChild(selectList);

                var option = document.createElement("option");
                option.value = "none";
                option.text = "-";
                selectList.appendChild(option);
                for (var i = 0; i < weaponList.length; i++) {
                    var option = document.createElement("option");
                    option.value = weaponList[i][0];
                    option.text = weaponList[i][1];
                    selectList.appendChild(option);
                }

                document.getElementById("weaponSelect").onchange = function() {updateEquipTip(0);};
            }

            function createHeadarmorSelect() {
                var holder = document.getElementById('headarmorSelectHolder');
                var selectList = document.createElement("select");
                selectList.id = "headarmorSelect";
                holder.appendChild(selectList);

                var option = document.createElement("option");
                option.value = "none";
                option.text = "-";
                selectList.appendChild(option);
                for (var i = 0; i < headarmorList.length; i++) {
                    var option = document.createElement("option");
                    option.value = headarmorList[i][0];
                    option.text = headarmorList[i][1];
                    selectList.appendChild(option);
                }

                document.getElementById("headarmorSelect").onchange = function() {updateEquipTip(1);};
            }


            function createBodyarmorSelect() {
                var holder = document.getElementById('bodyarmorSelectHolder');
                var selectList = document.createElement("select");
                selectList.id = "bodyarmorSelect";
                holder.appendChild(selectList);

                var option = document.createElement("option");
                option.value = "none";
                option.text = "-";
                selectList.appendChild(option);
                for (var i = 0; i < bodyarmorList.length; i++) {
                    var option = document.createElement("option");
                    option.value = bodyarmorList[i][0];
                    option.text = bodyarmorList[i][1];
                    selectList.appendChild(option);
                }

                document.getElementById("bodyarmorSelect").onchange = function() {updateEquipTip(2);};
            }  

            function createAcessorySelect() {
                var holder = document.getElementById('acessorySelectHolder');
                var selectList = document.createElement("select");
                selectList.id = "acessorySelect";
                holder.appendChild(selectList);

                var option = document.createElement("option");
                option.value = "none";
                option.text = "-";
                selectList.appendChild(option);
                for (var i = 0; i < acessoryList.length; i++) {
                    var option = document.createElement("option");
                    option.value = acessoryList[i][0];
                    option.text = acessoryList[i][1];
                    selectList.appendChild(option);
                }

                document.getElementById("acessorySelect").onchange = function() {updateEquipTip(3);};
            }   

            function resetAttributes() {
                var characterElements = document.getElementById("characterEditor").elements;
                var skillElements = document.getElementById("skillEditor").elements;
                var labels = document.getElementsByTagName('LABEL');

                for (var i = 1; i < 2; i++) {
                    characterElements[i].style.visibility = "hidden";
                    characterElements[i].disabled = true;
                }

                for (var i = 0; i < 6; i++) {
                    skillElements[i].style.visibility = "hidden";
                    skillElements[i].disabled = true;
                    skillElements[i].checked = false;
                }

                for (var i = 0; i < (labels.length); i++) {
                    labels[i].innerHTML = "Skill "+(i+1);
                    labels[i].style.visibility = "hidden";
                }

                document.getElementById("weaponSelect").disabled = true;
                document.getElementById("weaponSelect").value = "none";
                document.getElementById("headarmorSelect").disabled = true;
                document.getElementById("headarmorSelect").value = "none";
                document.getElementById("bodyarmorSelect").disabled = true;
                document.getElementById("bodyarmorSelect").value = "none";
                document.getElementById("acessorySelect").disabled = true;
                document.getElementById("acessorySelect").value = "none";

                document.getElementById("baseAtkStat").innerHTML = "???";
                document.getElementById("baseDefStat").innerHTML = "???";
                document.getElementById("baseMAtkStat").innerHTML = "???";
                document.getElementById("baseMDefStat").innerHTML = "???";
                document.getElementById("baseAgiStat").innerHTML = "???";

                document.getElementById("finalAtkStat").innerHTML = "???";
                document.getElementById("finalDefStat").innerHTML = "???";
                document.getElementById("finalMAtkStat").innerHTML = "???";
                document.getElementById("finalMDefStat").innerHTML = "???";
                document.getElementById("finalAgiStat").innerHTML = "???";

                document.getElementById("atkBar").innerHTML = "";
                document.getElementById("defBar").innerHTML = "";
                document.getElementById("matkBar").innerHTML = "";
                document.getElementById("mdefBar").innerHTML = "";
                document.getElementById("agiBar").innerHTML = "";

                document.getElementById("bodyStat").innerHTML = "???";
                document.getElementById("mindStat").innerHTML = "???";

                document.getElementById("atkBar").style.color = "";
                document.getElementById("defBar").style.color = "";
                document.getElementById("matkBar").style.color = "";
                document.getElementById("mdefBar").style.color = "";
                document.getElementById("agiBar").style.color = "";

                document.getElementById("nickname").value = "";
                document.getElementById("skillTip").innerHTML = "???";
                document.getElementById("equipTip").innerHTML = "???";
                document.getElementById("characterPrice").innerHTML = "Price: 0 $";
                document.getElementById("characterPrice").style.visibility = "hidden";
                document.getElementById("equipmentPrice").innerHTML = "Price: 0 $ (0 $)";
                document.getElementById("equipmentPrice").style.visibility = "hidden";
                document.getElementById("skillPrice").innerHTML = "Price: 0 $ (0 $)";
                document.getElementById("skillPrice").style.visibility = "hidden";
                document.getElementById("totalPrice").innerHTML = "Total Price: 0 $";
                document.getElementById("totalPrice").style.visibility = "hidden";
                document.getElementById("portrait").src = "https://i.postimg.cc/rsQnj4Zx/portrait-none.png";
            }
            
            function enableCustomization() {
                var character = document.getElementById("characterSelect").value;
                var characterElements = document.getElementById("characterEditor").elements;
                var skillElements = document.getElementById("skillEditor").elements;
                var equipmentElements = document.getElementById("equipmentEditor").elements;

                resetAttributes();
                if(character !== "none") {
                    for (var i = 1; i < 2; i++) {
                        characterElements[i].disabled = false;
                        characterElements[i].style.visibility = "visible";
                    }
                    
                    var skillCount = characterList[character-1][4];
                    for (var i = 0; i < skillCount; i++) {
                        skillElements[i].disabled = false;
                        skillElements[i].style.visibility = "visible";
                        
                    }

                    for (var i = 0; i < 4; i++) {
                        equipmentElements[i].disabled = false;
                        
                    }

                    document.getElementById("characterPrice").style.visibility = "visible";
                    document.getElementById("equipmentPrice").style.visibility = "visible";
                    document.getElementById("skillPrice").style.visibility = "visible";
                    document.getElementById("totalPrice").style.visibility = "visible";

                    updateInfo();

                    updateNames(character, skillCount);
                }
            }
            
            function updateNames(character, skillCount) {
                var labels = document.getElementsByTagName('LABEL');
                for (var i = 0; i < skillCount; i++) {
                    labels[i].innerHTML = skillList[(characterList[character-1][i+5])-1][1];
                    labels[i].style.visibility = "visible";
                }
                document.getElementById("portrait").src = characterList[character-1][3];
            }
            
            function exportCharacter() {
                var character = document.getElementById("characterSelect").value;
                var exportText = "";

                if(character == 'none') exportText = null;
                else {
                    var nickname = document.getElementById("nickname").value;
                    var skills = document.querySelectorAll("input[type='checkbox']");
                    var skillCount = characterList[character-1][4];
                    var weapon = document.getElementById("weaponSelect").value;
                    var headarmor = document.getElementById("headarmorSelect").value;
                    var bodyarmor = document.getElementById("bodyarmorSelect").value;
                    var acessory = document.getElementById("acessorySelect").value;
                    exportText = character + " / " + characterList[character-1][1] + " (" + nickname + ")";
                    for(var i=0; i < skillCount; i++) {
                        exportText += "\n - " + (i+1) + " / " + skillList[(characterList[character-1][i+5])-1][1] + ": " + skills[i].checked;
                    }

                    if(weapon!="none") exportText += "\n - w / " + weaponList[weapon-1][1] + " / " + weaponList[weapon-1][0];
                    else exportText += "\n - w / none / 0";
                    if(headarmor!="none")exportText += "\n - h / " + headarmorList[headarmor-1][1] + " / " + headarmorList[headarmor-1][0];
                    else exportText += "\n - h / none / 0";
                    if(bodyarmor!="none")exportText += "\n - b / " + bodyarmorList[bodyarmor-1][1] + " / " + bodyarmorList[bodyarmor-1][0];
                    else exportText += "\n - b / none / 0";
                    if(acessory!="none")exportText += "\n - a / " + acessoryList[acessory-1][1] + " / " + acessoryList[acessory-1][0];
                    else exportText += "\n - a / none / 0";
                }
                
                document.getElementById("exportText").value = exportText;
                editingTeam[parseInt(characterSlot)+3] = exportText;
                document.getElementById('teamData').value = JSON.stringify(teamList);
            }

            function importCharacter() {
                var importText = document.getElementById("exportText").value;
                var importArray = importText.split('\n');
                var skillElements = document.getElementById("skillEditor").elements;
                var skillCount = characterList[importArray[0][0]-1][4];

                document.getElementById("characterSelect").value = importArray[0][0];
                enableCustomization();
                document.getElementById("nickname").value = importArray[0].substring(importArray[0].indexOf("(")+1, importArray[0].indexOf(")"));

                for (var i = 0; i < skillCount; i++) {
                    if(importArray[i+1][importArray[i+1].length-2] == 'u') {
                        skillElements[i].checked = true;
                    }
                    else skillElements[i].checked = false;
                }

                i++;
                var weapon = importArray[i][importArray[i].length-1];
                if(weapon != '0') document.getElementById("weaponSelect").value = weapon;
                else document.getElementById("weaponSelect").value = "none";

                i++;
                var headarmor = importArray[i][importArray[i].length-1];
                if(headarmor != '0') document.getElementById("headarmorSelect").value = headarmor;
                else document.getElementById("headarmorSelect").value = "none";

                i++;
                var bodyarmor = importArray[i][importArray[i].length-1];
                if(bodyarmor != '0') document.getElementById("bodyarmorSelect").value = bodyarmor;
                else document.getElementById("bodyarmorSelect").value = "none";

                i++;
                var acessory = importArray[i][importArray[i].length-1];
                if(acessory != '0') document.getElementById("acessorySelect").value = acessory;
                else document.getElementById("acessorySelect").value = "none";

                exportCharacter();
                updateInfo();
            }

            function updateSkillTip(skillId) {
                var character = document.getElementById("characterSelect").value;
                var skillCount = characterList[character-1][4];
                if(skillId < skillCount) {
                    document.getElementById("skillTip").innerHTML= skillList[(characterList[character-1][skillId+5])-1][1] + " - " + skillList[(characterList[character-1][skillId+5])-1][7] + " $:<br>" + skillList[(characterList[character-1][skillId+5])-1][2];
                }
            }

            function updateEquipTip(equipType) {
                var tipText = "???";
                var equip = 0;
                switch(equipType) {
                    case 0:
                        equip = document.getElementById("weaponSelect").value;
                        if(equip != "none")
                            tipText = weaponList[equip-1][1] + " - " + weaponList[equip-1][5] + " $:<br>" + weaponList[equip-1][2];
                        break;
                    case 1:
                        equip = document.getElementById("headarmorSelect").value;
                        if(equip != "none")
                            tipText = headarmorList[equip-1][1] + " - " + headarmorList[equip-1][9] + " $:<br>" + headarmorList[equip-1][2];
                        break;
                    case 2:
                        equip = document.getElementById("bodyarmorSelect").value;
                        if(equip != "none")
                            tipText = bodyarmorList[equip-1][1] + " - " + bodyarmorList[equip-1][9] + " $:<br>" + bodyarmorList[equip-1][2];
                        break;
                    case 3:
                        equip = document.getElementById("acessorySelect").value;
                        if(equip != "none")
                            tipText = acessoryList[equip-1][1] + " - " + acessoryList[equip-1][9] + " $:<br>" + acessoryList[equip-1][2];
                        break;
                }

                updateInfo();
                document.getElementById("equipTip").innerHTML = tipText;
            }

            function updateInfo() {

                // Stat Stuff
                var character = document.getElementById("characterSelect").value;
                var weapon = document.getElementById("weaponSelect").value;
                var headarmor = document.getElementById("headarmorSelect").value;
                var bodyarmor = document.getElementById("bodyarmorSelect").value;
                var acessory = document.getElementById("acessorySelect").value;
                var atkBar = "";
                var defBar = "";
                var matkBar = "";
                var mdefBar = "";
                var agiBar = "";
                var gradient = [
                    [0,'ff0d0d'],
                    [12,'ff4e11'],
                    [24,'ff8e15'],
                    [36,'fab733'],
                    [48,'acb334'],
                    [60,'69b34c'],
                    [72,'4cb389'],
                    [84,'4cabb3'],
                    [96,'4c8db3'],
                ];
                
                var bonusAtk = 0;
                if(weapon != "none") bonusAtk += weaponList[weapon-1][4];
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

                finalAtk = characterList[character-1][13] + bonusAtk;
                finalDef = parseInt(((characterList[character-1][14] + bonusDef) * (100 + bonusRes)) / 100);
                finalMAtk = characterList[character-1][15] + bonusMAtk;
                finalMDef = parseInt(((characterList[character-1][16] + bonusMDef) * (100 + bonusRes)) / 100);
                finalAgi = characterList[character-1][17] + bonusAgi;

                document.getElementById("baseAtkStat").innerHTML = characterList[character-1][13];
                document.getElementById("baseDefStat").innerHTML = characterList[character-1][14];
                document.getElementById("baseMAtkStat").innerHTML = characterList[character-1][15];
                document.getElementById("baseMDefStat").innerHTML = characterList[character-1][16];
                document.getElementById("baseAgiStat").innerHTML = characterList[character-1][17];

                document.getElementById("finalAtkStat").innerHTML = finalAtk;
                document.getElementById("finalDefStat").innerHTML = finalDef;
                document.getElementById("finalMAtkStat").innerHTML = finalMAtk
                document.getElementById("finalMDefStat").innerHTML = finalMDef;
                document.getElementById("finalAgiStat").innerHTML = finalAgi;

                document.getElementById("bodyStat").innerHTML = characterList[character-1][11];
                document.getElementById("mindStat").innerHTML = characterList[character-1][12];

                for(let i = 0; i < finalAtk; i+=4) {
                    atkBar += "█";
                }
                for(let i = 0; i < finalDef; i+=4) {
                    defBar += "█";
                }
                for(let i = 0; i < finalMAtk; i+=4) {
                    matkBar += "█";
                }
                for(let i = 0; i < finalMDef; i+=4) {
                    mdefBar += "█";
                }
                for(let i = 0; i < finalAgi; i+=4) {
                    agiBar += "█";
                }

                var atkPer = (finalAtk*100/72);
                var defPer = (finalDef*100/72);
                var matkPer = (finalMAtk*100/72);
                var mdefPer = (finalMDef*100/72);
                var agiPer = (finalAgi*100/72);

                document.getElementById("atkBar").style.color = "rgb("+(128-atkPer)+","+(atkPer*1.39)+", "+(atkPer*0.34)+")";
                document.getElementById("defBar").style.color = "rgb("+(128-defPer)+","+(defPer*1.39)+", "+(defPer*0.34)+")";
                document.getElementById("matkBar").style.color = "rgb("+(128-matkPer)+","+(matkPer*1.39)+", "+(matkPer*0.34)+")";
                document.getElementById("mdefBar").style.color = "rgb("+(128-mdefPer)+","+(mdefPer*1.39)+", "+(mdefPer*0.34)+")";
                document.getElementById("agiBar").style.color = "rgb("+(128-agiPer)+","+(agiPer*1.39)+", "+(agiPer*0.34)+")";
                
                document.getElementById("atkBar").innerHTML = atkBar;
                document.getElementById("defBar").innerHTML = defBar;
                document.getElementById("matkBar").innerHTML = matkBar;
                document.getElementById("mdefBar").innerHTML = mdefBar;
                document.getElementById("agiBar").innerHTML = agiBar;

                // Price Stuff
                var skills = document.querySelectorAll("input[type='checkbox']");
                var skillCount = characterList[character-1][4];

                var characterPrice = characterList[character-1][18];
                var skillPrice = 0;
                var equipmentPrice = 0;
                var totalPrice = 0;

                for(var i=0; i < skillCount; i++) {
                    if(skills[i].checked) skillPrice += skillList[(characterList[character-1][i+5])-1][7];
                }

                if(weapon != "none") equipmentPrice += weaponList[weapon-1][5];
                if(headarmor != "none") equipmentPrice += headarmorList[headarmor-1][9];
                if(bodyarmor != "none") equipmentPrice += bodyarmorList[bodyarmor-1][9];
                if(acessory != "none") equipmentPrice += acessoryList[acessory-1][9];

                totalPrice = ((characterPrice/10)*(skillPrice + equipmentPrice))/4 + characterPrice;

                document.getElementById("characterPrice").innerHTML = "Price: " + characterPrice + " $";
                document.getElementById("skillPrice").innerHTML = "Price: " + skillPrice + " $ (" + ((characterPrice/10)*(skillPrice))/4 + " $)";
                document.getElementById("equipmentPrice").innerHTML = "Price: " + equipmentPrice + " $ (" + ((characterPrice/10)*(equipmentPrice))/4 + " $)";
                document.getElementById("totalPrice").innerHTML = "Total Price: " + totalPrice + " $";
            }

            function swapState(state) {
                switch(state) {
                    case 0:
                        document.getElementById("teamlist").classList.remove("hidden");
                        document.getElementById("teambuilder").classList.add("hidden");
                        document.getElementById("characterbuilder").classList.add("hidden");
                        break;
                    case 1:
                        document.getElementById("teamlist").classList.add("hidden");
                        document.getElementById("teambuilder").classList.remove("hidden");
                        document.getElementById("characterbuilder").classList.add("hidden");
                        break;
                    case 2:
                        document.getElementById("teamlist").classList.add("hidden");
                        document.getElementById("teambuilder").classList.add("hidden");
                        document.getElementById("characterbuilder").classList.remove("hidden");
                        break;
                }
            }
        </script>
    </head>
    <body>
        <div id="teamlist">
            <section id="teamListLayout">
                    <div class="listMenubar">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <button id="btn_return" type="button" onclick="mainMenu()"> X Main Menu</button>
                            <span>Teams</span>
                            <div id="save">
                                <button id="saveTeams" type="submit" name="submitAction" value="saveTeams">Save Teams</button>  
                            </div>
                            <input id="teamData" name="teamData" value="" type="hidden">
                        </form>
                    </div>
                    <div class="teamList">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div id="teamListHolder">
                                <script> loadTeamList(); </script>
                            </div>
                            <button id="deleteTeam" type="submit" name="submitAction" value="deleteTeam" style="display:none;"></button>
                            <input id="teamToDelete" type="hidden" name="teamToDelete" value=null>
                            <button id="newTeam" type="submit" name="submitAction" value="newTeam">+ New Team</button>
                        </form>
                    </div>
                </form>
            </section>
        </div>
        <div id="teambuilder" class="hidden">
            <section id="teamBuilderLayout">
                <div div class="teamMenubar">
                    <button onclick="swapState(0); loadTeamList();">< Team List</button>
                    <span id="teamName">
                        <input type="text" id="teamRename" title="Team Name" placeholder="Rename your Team">
                        <button onclick="renameTeam()">Rename Team</button>
                    </span>
                </div>
                <div div class="characterList">
                    <div id="teamHolder">
                    </div>
                </div>
            </section>
        </div>
        <div id="characterbuilder" class="hidden">
            <section id="characterBuilderLayout">
                <div class="characterMenubar">
                    <button onclick="swapState(1); loadTeam();">< Team</button>
                    <span>Team Member</span>
                </div>

                <div class="characterInfo">
                    <span class="title">Character</span>
                    <div id="characterPrice" class="price">Price: 0 $</div>
                    <form id="characterEditor">

                    <div id="characterSelectHolder">
                        <script> createCharacterSelect(); </script>
                    </div>

                    <br>

                    <div style="text-align: center;">
                    <img id="portrait" src="https://i.postimg.cc/rsQnj4Zx/portrait-none.png" width="140" height="140">

                    <br>

                    <input type="text" id="nickname" maxlength="32" placeholder="Character Nickname">
                    </div>

                    </form>
                </div>

                <div class="equipmentInfo">
                    <span class="title">Equipment</span>
                    <div id="equipmentPrice" class="price">Price: 0 $ (0 $)</div>
                    <form id="equipmentEditor">

                    <table style="width: 100%">
                        <tr>
                            <td style="width: 20%">Weapon:</td>
                            <td><div id="weaponSelectHolder" class="equipSelec">
                                <script> createWeaponSelect(); </script>
                            </div></td>
                        </tr>
                        <tr>
                            <td>Head:</td>
                            <td><div id="headarmorSelectHolder" class="equipSelec">
                                <script> createHeadarmorSelect(); </script>
                            </div></td>
                        </tr>
                        <tr>
                            <td>Body:</td>
                            <td><div id="bodyarmorSelectHolder" class="equipSelec">
                                <script> createBodyarmorSelect(); </script>
                            </div></td>
                        </tr>
                        <tr>
                            <td>Acessory:</td>
                            <td><div id="acessorySelectHolder" class="equipSelec">
                                <script> createAcessorySelect(); </script>
                            </div></td>
                        </tr>
                    </table>

                    <br>

                    <div id="equipTip">
                        ???
                    </div>

                    </form>
                </div>

                <div class="statInfo">
                    <span class="title">Stats</span>
                    <div id="statBox">
                        <table id="statBars">
                            <tr>
                                <th style="height: 10px;"></th>
                                <th style="height: 10px;">Base</th>
                                <td style="height: 10px;"></td>
                                <th style="height: 10px;">Final</th>
                            </tr> 
                            <tr>
                                <th>ATK</th>
                                <th id="baseAtkStat">???</th>
                                <td id="atkBar"></td>
                                <th id="finalAtkStat">???</th>
                            </tr> 
                            <tr>
                                <th>DEF</th>
                                <th id="baseDefStat">???</th>
                                <td id="defBar"></td>
                                <th id="finalDefStat">???</th>
                            </tr>
                            <tr>
                                <th>M.ATK</th>
                                <th id="baseMAtkStat">???</th>
                                <td id="matkBar"></td>
                                <th id="finalMAtkStat">???</th>
                            </tr>
                            <tr>
                                <th>M.DEF</th>
                                <th id="baseMDefStat">???</th>
                                <td id="mdefBar"></td>
                                <th id="finalMDefStat">???</th>
                            </tr>
                            <tr>
                                <th>AGI</th>
                                <th id="baseAgiStat">???</th>
                                <td id="agiBar"></td>
                                <th id="finalAgiStat">???</th>
                            </tr>
                        </table>
                        <table>
                            <tr>
                                <td style="text-align: center;">BODY: <span id="bodyStat">???</span></td>
                                <td style="text-align: center;">MIND: <span id="mindStat">???</span></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="skillInfo">
                    <span class="title">Skills</span>
                    <div id="skillPrice" class="price">Price: 0 $ (0 $)</div>
                    <form id="skillEditor">

                    <span onmouseover="updateSkillTip(0)" onchange="updateInfo()">
                        <input type="checkbox" id="skill1" value="">
                        <label for="skill1"> Skill 1</label>
                    </span>

                    <span onmouseover="updateSkillTip(1)" onchange="updateInfo()">
                        <input type="checkbox" id="skill2" value="">
                        <label for="skill2"> Skill 2</label>
                    </span>

                    <span onmouseover="updateSkillTip(2)" onchange="updateInfo()">
                        <input type="checkbox" id="skill3" value="">
                        <label for="skill3"> Skill 3</label>
                    </span>

                    <span onmouseover="updateSkillTip(3)" onchange="updateInfo()">
                        <input type="checkbox" id="skill4" value="">
                        <label for="skill4"> Skill 4</label>
                    </span>

                    <span onmouseover="updateSkillTip(4)" onchange="updateInfo()">
                        <input type="checkbox" id="skill5" value="">
                        <label for="skill5"> Skill 5</label>
                    </span>

                    <span onmouseover="updateSkillTip(5)" onchange="updateInfo()">
                        <input type="checkbox" id="skill6" value="">
                        <label for="skill6"> Skill 6</label>
                    </span>

                    <br><br>

                    <div id="skillTip">
                        ???
                    </div>

                    </form>
                </div>

                <div class="characterImportExport">
                    <span class="title">Import/Export</span>
                    <div id="totalPrice" class="price">Total Price: 0 $</div>
                    <form id="exportEditor">
                     
                    <input type="button" id="export" value="Export & Save" onClick="exportCharacter()">
                    <input type="button" id="import" value="Import" onClick="importCharacter()">
                    <br><br>
                    <textarea id="exportText"rows="9" cols="55" placeholder="Import/Export a Character"></textarea>

                    </form>
                </div>
            </section>
        </div>
    </body>
    <script>
        resetAttributes();
        document.getElementById('teamData').value = JSON.stringify(teamList);
    </script>
</html>