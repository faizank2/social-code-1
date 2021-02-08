<?php
//error_reporting(0); // Disable all errors.
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    http_response_code(404);
		header("Content-Type: text/html");// to tell browser about the data type
}
if(is_logged_in()==0){
  $res= array(
    'status' => '200 OK',
    'iscreated' => "error",
    'msg' => "You are not logged in."
  );
  echo json_encode($res);
  http_response_code(401);
  die();
}
header("Content-Type: application/json; charset=UTF-8");// to tell browser about the data type
if (isset($_POST["data"])) {
  require("../../db-con/db.php");
	try{
		$data = json_decode(base64_decode($_POST["data"]));
         $post_id=$data->post_id;
         $text=$data->text;

       	//validation
       	if (empty($post_id)) {
       		$res= array(
            'status' => '200 OK',
            'iscreated' => "error",
            'isdeleted' => "false",
            'msg' => "Invalid post Id"
          );
          echo json_encode($res);
          die(); 
         }
         
         if (empty($text)) {
          $res= array(
           'status' => '200 OK',
           'iscreated' => "error",
           'isdeleted' => "false",
           'msg' => "Empty comment can't be accepted"
         );
         echo json_encode($res);
         die(); 
        }
         
         
         //post id can only be numeric
         if (!preg_match("/^[0-9]*$/",$post_id)) {
            $res= array(
              'status' => '200 OK',
              'iscreated' => "error",
              'isdeleted' => "false",
              'msg' => "Invalid post Id"
            );
            echo json_encode($res);
            die();
          }

        //html sanitization
        $post_id=htmlspecialchars($post_id);
        $text=htmlspecialchars($text);

        //sql sanitization
        $post_id=mysqli_real_escape_string($conn,$post_id);
        $text=mysqli_real_escape_string($conn,$text);

        //convert comment text in base64
        $text=base64_encode($text);

        register_comment(is_logged_in(),$post_id,$text);
	}
	catch(Exception $e){
		$res= array(
            'status' => '200 OK',
            'iscreated' => "error",
            'isdeleted' => "false",
            'msg' => "Somthing went wrong"
          );
          echo json_encode($res);
          http_response_code(401);
          die();
	}
}
else{
  $res= array(
    'status' => '200 OK',
    'iscreated' => "error",
    'isdeleted' => "false",
    'msg' => "Somthing went wrong"
  );
  echo json_encode($res);
  http_response_code(401);
  die();
}


function check_post_exits($post_id){
  require("../../db-con/db.php");
  $sql="SELECT * from user_post where post_id='$post_id'";
      $result = mysqli_query($conn, $sql);
      if (mysqli_num_rows($result) ==1) {
        return true;
      }
      else{
        return false;
      }
}

function register_comment($user_id,$post_id,$text){
  require("../../db-con/db.php");
  $date=date("j F Y h:i:s A");
  if(check_post_exits($post_id)){//check if post exits
    $sql = "INSERT INTO post_comments (post_id,user_id,comment_text,time) VALUES ('$post_id','$user_id','$text','$date')";
    if (mysqli_query($conn, $sql)) {
      $sql1 = "SELECT * FROM post_comments where post_id='$post_id' and user_id='$user_id' and comment_text='$text' and time='$date'";
      $result1 = mysqli_query($conn, $sql1);
      if (mysqli_num_rows($result1) ==1) {
        while($row = mysqli_fetch_assoc($result1)) {
          $cmnt_id=$row["comment_id"];
        }
        $res= array(
          'status' => '200 OK',
          'iscreated' => "true",
          'comment_id' => $cmnt_id,
          'time' => $date
        );
        echo json_encode($res);
        die();
      }
    }
    else{
      $res= array(
        'status' => '200 OK',
        'iscreated' => "error"
      );
      echo json_encode($res);
      die();
    }
  }
  else{
    $res= array(
      'status' => '200 OK',
      'iscreated' => "error",
      'isdeleted' => "false",
      'msg' => "Post does not exits."
    );
    echo json_encode($res);
    die();
  }
}


//this function will check if user is logged in and return the id of the user else it will return 0/false
function is_logged_in(){
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
      if (mysqli_num_rows($result)==1) {
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