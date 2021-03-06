<?php
//error_reporting(0); // Disable all errors.
if ($_SERVER['REQUEST_METHOD'] !== "GET") {
    http_response_code(400);
    die();
}
header("Content-Type: application/json; charset=UTF-8");// to tell browser about the data type

if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
  $scheme = 'https://';
} else {
  $scheme = 'http://';
}
$host=$scheme.$_SERVER['SERVER_NAME'];

if(checklogin()!=0){
	session_start();
	get_me(checklogin());
}
else{
  $res= array(
    'status' => '200 OK',
    'msg' => "You are not logged in."
  );
  echo json_encode($res);
  http_response_code(401);
  die();
}


function get_me($id){
  if (!isset($_SESSION["index"])) {
    $_SESSION["index"]=0;
  }
  if (isset($_GET['action'])) {
    $_SESSION["index"]=0;
  }
  require("../../db-con/db.php");
  $sql = "SELECT * from user_post where u_id='$id' order by post_id DESC limit 10 OFFSET ".$_SESSION['index'];
  $result = mysqli_query($conn, $sql);
  $posts=[];
  if (mysqli_num_rows($result)>1) {
    while($row = mysqli_fetch_assoc($result)) {
      $post_text=base64_decode($row["post_text"]);
      $m_array=unserialize($row["post_media_id"]);
      for($i=0;$i<count($m_array);$i++){
        $tmp=$m_array[$i];
        $sql1 = "SELECT * from post_media where media_id='$tmp'";
        $result1 = mysqli_query($conn, $sql1);
        if (mysqli_num_rows($result1)==1) {
          while($row1 = mysqli_fetch_assoc($result1)) {
            $m_array[$i]=$GLOBALS['host']."/fyp/user-content/post-media/".$row1["media_name"];
          }
        }
      }
      $post=array(
        'post_id' => $row["post_id"],
        'post_author' => get_user_details($row["u_id"]),
        'post_text' => htmlspecialchars($post_text, ENT_QUOTES, 'UTF-8'),
        'post_media' => $m_array,
        'post_time' => $row["post_time"],
        'total_likes' => total_likes($row["post_id"]),
        'me_liked' => me_liked($id,$row["post_id"]),
        'total_comments' => total_comments($row["post_id"])
      );
    array_push($posts,$post); 
    }

    $res= array(
      'status' => '200 OK',
      'posts' => $posts
    );
      echo json_encode($res);
    $_SESSION["index"]+=10;
    die();
  }
  else{
    $res= array(
      'status' => '200 OK',
      'posts' => "null"
    );
    echo json_encode($res);
    die();
  }
}

function total_likes($post_id){
  require("../../db-con/db.php");
  $sql = "SELECT * from post_stars where post_id='$post_id'";
  $result = mysqli_query($conn, $sql);
  return mysqli_num_rows($result);
}

function total_comments($post_id){
  require("../../db-con/db.php");
  $sql="SELECT * from post_comments where post_id=$post_id";
  $result = mysqli_query($conn, $sql);
  return mysqli_num_rows($result);
}

function me_liked($user_id,$post_id){
  require("../../db-con/db.php");
  $sql = "SELECT * from post_stars where post_id='$post_id' and user_id='$user_id'";
  $result = mysqli_query($conn, $sql);
  if (mysqli_num_rows($result)==1) {
    return true;
  }
  else{
    return false;
  }
}

function get_user_details($id){
  require("../../db-con/db.php");
  $sql = "SELECT * from users where id='$id'";
  $result = mysqli_query($conn, $sql);
  if (mysqli_num_rows($result)==1) {
    while($row = mysqli_fetch_assoc($result)) {
      $res= array(
        'user_id' => $row["id"],
        'Name' => htmlspecialchars(ucfirst($row["firstname"]), ENT_QUOTES, 'UTF-8')." ".htmlspecialchars(ucfirst($row["lastname"]), ENT_QUOTES, 'UTF-8'),
        'username' => $row["uid"],
        'dp_link' => $GLOBALS['host']."/fyp/user-content/display-pic/".$row["dplink"]
      );
    }
    return $res;
  }
  else{
    $res= array(
      'status' => '200 OK',
      'posts' => 'Null',
      'msg' => 'something went wrong'
    );
    echo json_encode($res);
    die();
  }
}

function checklogin(){
  if (isset($_COOKIE["json_token"]) && isset($_COOKIE["ultra_cookie"]) && isset($_COOKIE["k2_cookie"]) && isset($_COOKIE["k2_extra"])) {
      require("../../db-con/db.php");
      $json=$_COOKIE["json_token"];
      $ultra=$_COOKIE["ultra_cookie"];
      $k2=$_COOKIE["k2_cookie"];
      $extra=$_COOKIE["k2_extra"];
      $json=mysqli_real_escape_string($conn,$json);
      $ultra=mysqli_real_escape_string($conn,$ultra);
      $k2=mysqli_real_escape_string($conn,$k2);
      $extra=mysqli_real_escape_string($conn,$extra);

      $json=base64_encode($json);
      $ultra=base64_encode($ultra);
      $k2=base64_encode($k2);
      $extra=base64_encode($extra);
      $sql = "SELECT * FROM do_login where k2_cookie='$k2' and ultra_cookie='$ultra' and json_token='$json' and extra='$extra'";
      $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) >0) {
          while($row = mysqli_fetch_assoc($result)) {
          $uid=$row["uid"];
          }
          return $uid; 
     }
     else{
      return 0;
     }     

	}
	else{
    return 0;
	} 
}
?>
