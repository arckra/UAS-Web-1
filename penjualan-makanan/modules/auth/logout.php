<?php
// Hancurkan session
session_destroy();

// Redirect ke halaman login
redirect('auth/login');
?>