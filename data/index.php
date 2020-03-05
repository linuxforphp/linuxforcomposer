<?php
/* http://example.com:
<?php var_dump($_FILES); ?>
*/

// Create a cURL handle
$ch = curl_init('http://localhost/index-server.php');

$cfile = curl_file_create('example.txt', 'text/plain', 'test_name');

// Assign POST data
$postdata = ['file' => $cfile];
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

// Execute the handle
echo curl_exec($ch);
