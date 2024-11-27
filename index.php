<?php
//ginamit para magamit ang mga session variable sa script.
session_start();

//connection sa database, kinukuha ang servername, username, password, at pangalan ng database
//kapag hindi nag connect or connection failed, may lalabas na error message.
$conn = new mysqli('localhost', 'root', 'root', 'guessing_game'); 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$message = "";
// randomize ng number from 1 to 100 na huhulaan ng user
if (!isset($_SESSION['target_number'])) {
    $_SESSION['target_number'] = rand(1, 100);
//tina-track kung ilan ang nagawang attempts ni user para mahulaan yung number.
    $_SESSION['attempts'] = 0;
}

//nag proprocess ng post request na tinatanggap ang pangalan ng user. napupunta ito sa
//session variable na name.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name'])) {
        $_SESSION['name'] = ($_POST['name']);
    }
//kapag nag submit si user ng guess kinukuha sya guess na input field tapos cino-convert
//sa integer gamit yug intval. then nadadagdagan yung attempts ng isa.
    if (isset($_POST['guess'])) {
        $guess = intval($_POST['guess']);
        $_SESSION['attempts']++;

        //kapag mataas ang guess, may lalabas na "too high, try again", kapag mababa
        //"too low, try again". kapag tama naman, may lalabas ng message na congratulations
        //kasama yung name ng user at kung ilang attempts bago mahulaan ang number.
        if ($guess > $_SESSION['target_number']) {
            $message = "Too high! Try again.";
        } elseif ($guess < $_SESSION['target_number']) {
            $message = "Too low! Try again.";
        } else {
            $message = "Congratulations, {$_SESSION['name']}! You guessed the number in {$_SESSION['attempts']} attempts.";

            //kapag nahulaan ng user yung tamang number, mapupunta yung name, at number
            //of attempts sa database kasama ng timestamp. 
            $sql = $conn->prepare("INSERT INTO leaderboard (name, attempts, date_played) VALUES (?, ?, NOW())");
            $sql->bind_param("si", $_SESSION['name'], $_SESSION['attempts']);
            $sql->execute();
            $sql->close();

            //after mahulaan, mag re-reset yung target number, attempts at names, using
            //unset.
            unset($_SESSION['target_number']);
            unset($_SESSION['attempts']);
            unset($_SESSION['name']);
        }
    }
}

//sql query na kinukua ang top 10 players naka sort sya kung ilang attempts at sa date at
//time. yung result ng query ay naka store sa resultlead.
$sqllead = "
    SELECT 
        name, 
        attempts, 
        DATE(date_played) AS play_date, 
        TIME(date_played) AS play_time 
    FROM leaderboard 
    ORDER BY attempts ASC, date_played ASC 
    LIMIT 10";
$resultlead = $conn->query($sqllead);
?>

<!--  -->
<!DOCTYPE html>
<html>
<head>
    <title>Guessing Game</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }
        .container {
            text-align: center;
        }
        table {
            margin: 20px;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>NUMBER GUESSING GAME</h1>
        <p><?php echo $message; ?></p>

        <?php if (!isset($_SESSION['name'])): ?>
            <form method="post">
                <label for="name"><B>Enter your name:</B></label><br><br>
                <input type="text" name="name" id="name" required>
                <button type="submit">Start Game</button>
            </form>
        <?php else: ?>
            <form method="post">
                <label for="guess"><b>Your Guess:</b></label><br> <br>
                <input type="number" name="guess" id="guess" required min="1" max="100">
                <button type="submit">Submit Guess</button>
            </form>
        <?php endif; ?>

        <h2>TOP 10 PLAYERS</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Attempts</th>
                <th>Date Played</th>
                <th>Time Played</th>
            </tr>
            <?php if ($leaderboardResult->num_rows > 0): ?>
                <?php while ($row = $leaderboardResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['attempts']; ?></td>
                        <td><?php echo $row['play_date']; ?></td>
                        <td><?php echo $row['play_time']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No records found.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
