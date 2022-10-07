<?php 

class server
{
	private $room ;
	private $hostname = "localhost";
	private $username = "blackoleg";
	private $password = "123";
	private $dbName = "blackj"; 
	private $link;

	function __construct($room)
	{
		$this->$room = $room;
		$this->connect(); 
	}
	private function connect(){
		$this->$link = mysqli_connect($this->$hostname, $this->$username, $this->$password, $this->$dbName);
	}
	private function mail($email,$name,$pass){
		$name = $_SESSION['name'];
		$email = $_SESSION['email'];
		$name = htmlspecialchars($name);
		$email = htmlspecialchars($email);
		$name = urldecode($name);
		$email = urldecode($email);
		$name = trim($name);
		$email = trim($email);

		$subject = "Регистрация в игре Блэкджек";
		$charset = "utf-8";
		$headers="From: blackject@gmail.com\r\n";
		$headers.="Content-type: text/html; charset=$charset\r\n";
		$headers.="MIME-Version: 1.0\r\n";
		$headers.="Date: ".date('D, d M Y h:i:s O')."\r\n";
		$msg = $name."Вы зарегистрировались на нашем сайте, данные для входа:\n Пароль:" . $pass . "\nЛогин:" . $name;
		mail($email, $subject, $msg, $headers);
	}
	public function chek_log(){
		if(isset($_POST['exit'])){
			unset($_SESSION);
			unset($_POST);
		}
		if (isset($_POST['name']) && isset($_POST['pas'])){
			$mail = $_POST['email'];
			$name = $_POST['name'];
			$pas = $_POST['pas'];
			$sql = "INSERT INTO users (id_user, user_name, password, total_score, email) VALUES (NULL, '$name', '$pas', '0', '$mail')";
			mysqli_query($this->$link,$sql);
			$_SESSION['user_name'] = $name;
		    $_SESSION['user_score'] = 0;
		     //$_SESSION['id_user'] = $row['id_user'];
			//this->mail($mail,$name,$pas);

		}
		if (isset($_POST['name']) && isset($_POST['password']))
		{
			$username=$_POST['name'];
			$userpass=$_POST['password'];
		    $stmt = $this->$link->prepare("SELECT * FROM users WHERE user_name=? and password=?");
		    $stmt->bind_param("ss", $username, $userpass);
		    $stmt->execute();
		    $result = $stmt->get_result();
		    if($result->num_rows>0){
		        while($row = $result->fetch_assoc()) {
		            $_SESSION['user_name'] = $row['user_name'];
		            $_SESSION['user_score'] = $row['total_score'];
		            $_SESSION['id_user'] = $row['id_user'];
		           // header("Location: ../pages/user_page.php");
		        }
		        $stmt->close();
		        unset($_POST);
			}
			else{
		        $mes="Неверный логин или пароль";
		         header("Location: login2.html");
		       // header("Location: ../pages/user_page.php?message=$mes");
		    }
		}
		if(!isset($_SESSION['user_name'])){
			header("Location: login2.html");
		}
	}
	public function stop_play($id){
		$sql = "UPDATE rooms1 SET stop_play = '1' WHERE id_room = '$this->$room' AND id_player = '$id'";
		mysqli_query($this->$link, $sql);
		}
	private function check_win(){
		$sql = "SELECT stop_play FROM rooms1 WHERE stop_play = '1'";
		$result = mysqli_query($this->$link,$sql);
		$n_row = mysqli_num_rows($result);
		if($n_row == 4){
			$sql = "SELECT score_player,id_player FROM rooms1 WHERE id_room = '$this->$room'";
			$result = mysqli_query($this->$link,$sql);
			
			$win_score = 0;
			while($row = mysqli_fetch_row($result)){
				if($row[0] > $win_score && $row[0] < 22){
					$win_score = $row[0];
				}
			}
			$win_id = 0;
			$sql = "SELECT score_player,id_player FROM rooms1 WHERE id_room = '$this->$room'";
			$result = mysqli_query($this->$link,$sql);
			while($row = mysqli_fetch_row($result)){
				if($win_score == $row[0]){
					$win_id =$row[1];
				}
			}
			//echo $win_id;
			$sql = "SELECT total_score FROM users WHERE id_user = '$win_id'";
			$result = mysqli_query($this->$link,$sql);
			$row = mysqli_fetch_row($result);
			$point = $row[0] + 3;
			$sql = "UPDATE users SET total_score = '$point ' WHERE id_user = '$win_id'";
			mysqli_query($this->$link,$sql);
			return 2;
		}else{
			return 0;
		}

		}
	public function new_game(){
		if($this->check_win() == 2){
			echo "new_game";
			$sql = "UPDATE rooms1 SET card_player = '' WHERE id_room = '$this->$room'";
			mysqli_query($this->$link,$sql);
			$sql = "UPDATE room SET steps = '0' WHERE id_room = '$this->$room'";
			mysqli_query($this->$link,$sql);
			$sql = "UPDATE room SET cards = 'C2C3C4C5C6C7C8C9CBCDCKCAB2B3B4B5B6B7B8B9BBBDBKBAP2P3P4P5P6P7P8P9PBPDPKPAT2T3T4T5T6T7T8T9TBTDTKT2' WHERE id_room = '$room'";//96
			mysqli_query($this->$link,$sql);
			}
		}
	public function try_pick($pick){// r -1 , p - 1/0
		if($this->check_win() == 2){
			$sql = "SELECT cards FROM room WHERE id_room = '$room'";
			$result = mysqli_query($this->$link,$sql);
			$row = mysqli_fetch_row($result);
			if(strlen($row[0]) != 96){
				echo json_encode(2);
				return;
			}else{
				echo json_encode(3);
			}
		}
		$sql = "SELECT steps FROM room WHERE id_room = '$this->$room'";
		$result = mysqli_query($this->$link, $sql);
		$row = mysqli_fetch_row($result);
		if($row[0] == 0){
			first_step();
			ratio_score_room(1);
		}else{
			$sql = "SELECT active_user_id FROM room WHERE id_room = '$this->$room'";
			$result = mysqli_query($this->$link, $sql);
			$row = mysqli_fetch_row($result);
			echo $row[0];
			if($row[0] == $_SESSION['id_user']){
				if($pick == 2){
					//change_active_player($link,$room,$row[0]);
					echo json_encode(0);
					
				}else{
					if($pick == 1){
						player_pick($row[0]);
						ratio_score_room();
						change_active_player($row[0]);
					}else{
						stop_play($row[0]);
						change_active_player($row[0]);
						echo json_encode(0);	
					}
				}
			}
			if($row[0] < 6){
				bot_pick(1,$row[0]);
				ratio_score_room();
				$new_activ = change_active_player($row[0]);
				if($new_activ == $_SESSION['id_user'])
					echo json_encode(1);//можно ходить!!
					else
						echo json_encode(0);		
				}
				else{
					if($row[0] == $_SESSION['id_user'] ){
						echo json_encode(1);
					}else{
						echo json_encode(0);
					}
				}
			
			}
			

		}

	public function player_pick($id_player){
		$sql = "SELECT id_place FROM rooms1 WHERE id_player = '$id_player'";
		$result = mysqli_query($this->$link, $sql);
		$row = mysqli_fetch_row($result);
		$this->take_card_user($row[0],2);
		}
	public function change_active_player($id_real_act){
		$sql = "SELECT id_place FROM rooms1 WHERE id_player = '$id_real_act'";
		$result = mysqli_query($this->$link, $sql);
		$row = mysqli_fetch_row($result);
		$new_place = 0;

		if($row[0] % 4 == 0){
			$new_place = 1;
		}else{
			$new_place += $row[0] + 1;
		}
		$sql = "SELECT id_player FROM rooms1 WHERE id_place = '$new_place'";
		$result = mysqli_query($this->$link, $sql);
		$row = mysqli_fetch_row($result);
		$sql = "UPDATE room SET active_user_id = '$row[0]' WHERE id_room = '$this->$room'";
		mysqli_query($this->$link, $sql);
		return $row[0];
		}
	public function bot_pick($id_bot){
		$sql = "SELECT score_player,id_place FROM rooms1 WHERE id_player = '$id_bot'";
		$result = mysqli_query($this->$link, $sql);
		$row = mysqli_fetch_row($result);
		if($row[0] > 21){
			return;
		}
		if($row[0] < 10){
			$this->take_card_user($row[1],2);
		}else{
			$rand = rand(0,1);
			if($rand){
				$this->take_card_user($row[1],2);
			}else{
				$this->stop_play($id_bot);
			}
		}
		}
	public function first_step(){
		$sql ="UPDATE rooms1 SET score_player = '0' WHERE  id_room = '$this->$room'";
		mysqli_query($this->$link, $sql);
		$sql ="UPDATE rooms1 SET stop_play = '0' WHERE  id_room = '$this->$room'";
		mysqli_query($this->$link, $sql);

		for($i = 1; $i < 5 ;$i++){
			take_card_user($i,4);
		}
		$sql = "SELECT id_player FROM rooms1 WHERE id_place = '1'";
		$result = mysqli_query($this->$link, $sql);
		$row = mysqli_fetch_row($result);
		$sql = "UPDATE room SET active_user_id = '$row[0]' WHERE id_room = '$this->$room'";
		mysqli_query($this->$link, $sql);
		$sql = "UPDATE room SET steps = '1' WHERE id_room = '$this->$room'";
		mysqli_query($this->$link, $sql);
		}
	public function take_card_user($get_user_id,$get_simbol_card){// id_place указываем
		$sql = "SELECT cards FROM room WHERE id_room = '$this->$room'";
		$result = mysqli_query($this->$link, $sql);
		$row = mysqli_fetch_row($result);
		$card = substr($row[0], -$get_simbol_card);
		$new_cards = substr($row[0],0,strlen($row[0])-$get_simbol_card);
		$sql = "UPDATE room SET cards = '$new_cards' WHERE id_room = '$this->$room'";
		mysqli_query($this->$link, $sql);
		$sql = "SELECT card_player FROM rooms1 WHERE id_room = '$this->$room' AND  id_place = '$get_user_id'";
		$result = mysqli_query($this->$link, $sql);
		$row = mysqli_fetch_row($result);
		$card = $card . $row[0];
		$sql = "UPDATE rooms1 SET card_player = '$card' WHERE id_room = '$this->$room' AND id_place = '$get_user_id'";
		mysqli_query($this->$link, $sql);
		}

	public function ratio_score_room(){
		$sql = "SELECT card_player FROM rooms1 WHERE id_room = '$this->$room'";
		$result = mysqli_query($this->$link, $sql);
		$j = 0;
		while($row = mysqli_fetch_row($result)){
			$score_r = 0;
			$j += 1;
			for($i = 2; $i <= strlen($row[0]); $i+=2){// 6
				$card = substr($row[0], strlen($row[0]) - $i, 2);
				$file_handle = fopen("card.txt", "r");
				while (!feof($file_handle)) {
				   $line = fgets($file_handle);
				   $tm = strpos($line,$card);
				   if($tm === false){
				   }else{
				   		$score_r += (int)substr($line,3,2);
				   }
				}
				fclose($file_handle);
			}
			$sql = "UPDATE rooms1 SET score_player = '$score_r' WHERE id_place = $j";
			mysqli_query($this->$link, $sql);
			if($score_r >= 21){
				$sql = "UPDATE rooms1 SET stop_play = '1' WHERE id_place = $j";
				mysqli_query($this->$link, $sql);
			}
		}
		}
		//C2C3C4C5C6C7C8C9CBCDCKCAB2B3B4B5B6B7B8B9BBBDBKBAP2P3P4P5P6P7P8P9PBPDPKPAT2T3T4T5T6T7T8T9TBTDTKTA

	public function in_rooms(){
		$user_act_id = $_SESSION['id_user'];
		$sql = "SELECT id_player FROM rooms1 WHERE id_room = '$this->$room' AND id_player = '$user_act_id'";
		$result = mysqli_query($this->$link, $sql);
		$n_row = mysqli_num_rows($result);
		if($n_row == 1){
			return;
		}

		$sql = "SELECT * FROM rooms1 WHERE id_room = '$this->$room' AND id_player < 6";
		$result = mysqli_query($this->$link, $sql);
		$n_row = mysqli_num_rows($result);
		if($n_row != 0){
			$row = mysqli_fetch_row($result);
			$sql = "UPDATE rooms1 SET id_player = '$user_act_id' WHERE id_place = '$row[0]'";
			mysqli_query($this->$link, $sql);
			mysqli_free_result($result);

		}
	}

	public function load_user_rooms(){
		$select = "SELECT u.user_name AS user_n ,r1.steps, u1.user_name AS active_name FROM rooms1 r JOIN users u ON u.id_user = id_player JOIN room r1 ON r1.id_room = r.id_room JOIN users u1 ON u1.id_user = r1.active_user_id WHERE r.id_room = '$this->$room'";

		$res_1 = mysqli_query($this->$link,$select);

		$res = mysqli_fetch_all($res_1, MYSQLI_ASSOC);//MYSQLI_NUM MYSQLI_ASSOC
		echo json_encode($res);

		}
	public function load_user_score($id_user){

		$sql = "SELECT score_player,id_place FROM rooms1 WHERE id_player = '$id_user'";
		$result = mysqli_query($this->$link, $sql);
		$row = mysqli_fetch_row($result);
		echo json_encode($row);

		}
	public function return_card($id_user){
		$sql = "SELECT stop_play FROM rooms1 WHERE stop_play = '1' AND id_room = '$this->$room'";
		$result = mysqli_query($this->$link, $sql);
		$n_row = mysqli_num_rows($result);
		if($n_row == 4){
			$sql = "SELECT card_player,score_player FROM rooms1 WHERE id_room = '$this->$room'";
			$result = mysqli_query($this->$link, $sql);
			$res = mysqli_fetch_all($result, MYSQLI_ASSOC);//MYSQLI_NUM MYSQLI_ASSOC
			echo json_encode($res);
		}else{
			$sql = "SELECT card_player FROM rooms1 WHERE id_room = '$this->$room' AND id_player = '$id_user'";
			$result = mysqli_query($this->$link, $sql);
			$res = mysqli_fetch_all($result, MYSQLI_ASSOC);//MYSQLI_NUM MYSQLI_ASSOC
			echo json_encode($res);
		}
		}
	public function load_title(){
		if($this->check_win($this->$link,$this->$room) == 2){
			echo json_encode(2);
		}else{
			$sql = "SELECT u.user_name FROM room r JOIN users u ON u.id_user = r.active_user_id";
			$result = mysqli_query($this->$link, $sql);
			$row = mysqli_fetch_row($result);
			echo json_encode($row[0]);
		}
		}
}
