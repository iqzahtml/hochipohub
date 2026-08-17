<?php

$password = 'Admin123456';

echo password_hash(
    $password,
    PASSWORD_DEFAULT
);