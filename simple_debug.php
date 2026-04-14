<?php
$conn = mysqli_connect('localhost', 'root', '', 'hall_allocation');
$res = mysqli_query($conn, "SHOW CREATE TABLE bookings");
$row = mysqli_fetch_row($res);
file_put_contents('schema.txt', $row[1]);
