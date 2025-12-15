<?php
session_start();

// Make sure user session exists (player name + session id)
if (!isset($_SESSION['player_name']) || !isset($_SESSION['session_id'])) {
    die("Session not set for leaderboard");
}

$playerName = $_SESSION['player_name'];
$currentSession = $_SESSION['session_id'];

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "risingwaters";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// -----------------------------
// GET TOP 20 LEADERBOARD
// -----------------------------
$topQuery = "
    SELECT
        u.username AS name,
        g.score,
        g.session,
        TIMESTAMPDIFF(SECOND, g.start_time, g.end_time) AS timer
    FROM games g
    JOIN users u ON g.player_id = u.id
    WHERE g.sessionstatus = 'finished'
    ORDER BY g.score DESC, timer ASC
    LIMIT 20
";

$topResult = $conn->query($topQuery);

// Store for display
$leaderboard = [];
$currentPlayerRank = null;
$currentPlayerScore = null;
$currentPlayerTime = null;

$rank = 1;

while ($row = $topResult->fetch_assoc()) {
    $leaderboard[] = [
        'rank' => $rank,
        'name' => $row['name'],
        'score' => $row['score'],
        'time' => $row['timer']
    ];

    // CORRECT FIX: Match by session ID, not username
    if ($row['session'] == $currentSession) {
        $currentPlayerRank = $rank;
        $currentPlayerScore = $row['score'];
        $currentPlayerTime = $row['timer'];
    }

    $rank++;
}

// ---------------------------------------------------
// IF PLAYER NOT IN TOP 20, GET THEIR TRUE RANK
// ---------------------------------------------------
if ($currentPlayerRank === null) {

    // Get this exact session’s result
    $playerQuery = "
        SELECT
            u.username AS name,
            g.score,
            TIMESTAMPDIFF(SECOND, g.start_time, g.end_time) AS timer
        FROM games g
        JOIN users u ON g.player_id = u.id
        WHERE g.session = $currentSession
        LIMIT 1
    ";
    $playerResult = $conn->query($playerQuery);

    if ($playerResult->num_rows === 1) {
        $playerData = $playerResult->fetch_assoc();
        $currentPlayerScore = $playerData['score'];
        $currentPlayerTime = $playerData['timer'];
    }

    // Count how many players are better to calculate rank
    $rankQuery = "
        SELECT COUNT(*) AS betterRank
        FROM games
        WHERE sessionstatus = 'finished'
          AND (score > $currentPlayerScore
          OR (score = $currentPlayerScore AND TIMESTAMPDIFF(SECOND, start_time, end_time) < $currentPlayerTime))
    ";

    $rankResult = $conn->query($rankQuery);
    $rowRank = $rankResult->fetch_assoc();

    $currentPlayerRank = $rowRank['betterRank'] + 1;
}

$conn->close();

// ---------------------------------------------
// OUTPUT FOR YOUR FRONTEND (ADJUST IF NEEDED)
// ---------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rising Waters - Leaderboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Luckiest+Guy&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Luckiest Guy', cursive;
            display: flex;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            background: #000;
        }

        .background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            object-fit: cover;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.5s ease-in;
        }

        .background-video.loaded {
            opacity: 1;
        }

        .leaderboard-container {
            width: 600px;
            position: relative;
        }

        h2 {
            text-align: center;
            margin-bottom: 1rem;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
            font-size: 3rem;
        }

        .leaderboard-scroll {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .player-card {
            display: flex;
            justify-content: space-between;
            border: 2px solid #000;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.9);
        }

        .player-card div {
            flex: 1;
            text-align: center;
            border-right: 1px solid #000;
            padding: 0.5rem 0;
        }

        .player-card div:last-child {
            border-right: none;
        }

        .field-title {
            font-size: 1rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 0.25rem;
        }

        .current-player-rank {
            margin-top: 1rem;
            border-top: 2px dashed #000;
            padding-top: 0.75rem;
            background: rgba(255, 255, 255, 0.9);
        }

        .highlight {
            background: rgba(255, 255, 200, 0.9);
        }

        .play-again-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            font-family: 'Luckiest Guy', cursive;
            font-size: 2rem;
            background: transparent;
            color: #fff;
            border: none;
            cursor: pointer;
            text-decoration: underline;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
            transition: transform 0.2s;
        }

        .play-again-btn:hover {
            transform: scale(1.1);
        }
    </style>
</head>

<body>

    <video class="background-video" autoplay loop playsinline preload="auto"
        onloadeddata="this.classList.add('loaded')">
        <source src="../Animations/Homepage.mp4" type="video/mp4" />
    </video>

    <div class="leaderboard-container">
        <h2>Leaderboard</h2>

        <div class="leaderboard-scroll">
            <?php foreach ($leaderboard as $row): ?>
                <div class="player-card">
                    <div>
                        <div class="field-title">Rank</div>
                        <?= $row['rank'] ?>
                    </div>

                    <div>
                        <div class="field-title">Name</div>
                        <?= htmlspecialchars($row['name']) ?>
                    </div>

                    <div>
                        <div class="field-title">Score</div>
                        <?= $row['score'] ?>
                    </div>

                    <div>
                        <div class="field-title">Time (s)</div>
                        <?= $row['time'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="player-card current-player-rank highlight">
            <div>
                <div class="field-title">Rank</div>
                <?= $currentPlayerRank ?>
            </div>
            <div>
                <div class="field-title">Name</div>
                <?= htmlspecialchars($playerName) ?>
            </div>
            <div>
                <div class="field-title">Score</div>
                <?= $currentPlayerScore ?>
            </div>
            <div>
                <div class="field-title">Time (s)</div>
                <?= $currentPlayerTime ?>
            </div>
        </div>
    </div>

    <button class="play-again-btn" onclick="window.location.href='../Pages/Homepage.html'">
        Play Again
    </button>

</body>
</html>
