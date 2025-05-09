<?php

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();

}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Activity Monitor</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <h2 class="text-2xl font-bold text-center mt-4">Live PC Activity Monitoring</h2>

    <div class="logout-container text-center mt-2">
        <form action="logout.php" method="post">
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">Logout</button>
        </form>
    </div>

    <!-- PC Grid Layout -->
    <div class="flex justify-center mt-10">
        <div class="grid grid-cols-4 gap-4 bg-[#002629] p-10 rounded shadow-lg text-white text-xs text-center">
            <?php
            include('Station_generator.php');
            generateStation();
            ?>
        </div>
    </div>


    <!-- Live Log List -->
    <ul id="log-list" class="mt-10 max-w-4xl mx-auto px-4 list-disc"></ul>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Your JS -->
    <script src="script.js" type="module"></script>
</body>

</html>