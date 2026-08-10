<?php
 require_once "includes/autoloader.php";
// deleting operation
$json = file_get_contents('php://input');
$data = json_decode($json);
$process = new Process();


if(isset($data->mass_delete)){

    $all_id = $data->id;
    $delete = $process->delete($all_id);
    if($delete) {
        
        header('Location: index.php');
    }else{
        echo "Failed to delete";
    }
}

?>
