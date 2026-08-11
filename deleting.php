<?php
require_once "includes/autoloader.php";

// Read the JSON data sent in the request body
$json = file_get_contents('php://input');
$data = json_decode($json);
$process = new Process();

// Check if a mass delete request was sent
if (isset($data->mass_delete)) {

    $all_id = $data->id;

    // Delete the selected products
    $delete = $process->delete($all_id);
    if ($delete) {

        // Return to the main product page after deletion  
        header('Location: index.php');
    } else {
        // Show an error if the deletion failed
        echo "Failed to delete";
    }
}
