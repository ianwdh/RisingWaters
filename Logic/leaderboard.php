<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db   = "risingwaters";

// Database connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch top 20 finished players
$sql = "
    SELECT name, score, TIMESTAMPDIFF(SECOND, start_time, IFNULL(end_time, NOW())) AS timer
    FROM players
    WHERE status = 'finished'
    ORDER BY score DESC, timer ASC
    LIMIT 20
";
$result = $conn->query($sql);

$leaderboard = [];
$currentPlayerRank = null;
$currentPlayerScore = null;
$currentPlayerTime = null;
$rank = 1;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['rank'] = $rank;
        $leaderboard[] = $row;

        if (isset($_SESSION['player_name']) && $_SESSION['player_name'] === $row['name']) {
            $currentPlayerRank = $rank;
            $currentPlayerScore = $row['score'];
            $currentPlayerTime = $row['timer'];
        }

        $rank++;
    }
}

// If player is not already captured in top 20, calculate their rank
if (isset($_SESSION['player_name']) && $currentPlayerRank === null) {
    $sql = "SELECT score, TIMESTAMPDIFF(SECOND, start_time, IFNULL(end_time, NOW())) AS timer
            FROM players
            WHERE name = '" . $conn->real_escape_string($_SESSION['player_name']) . "'";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $playerRow = $res->fetch_assoc();
        $currentPlayerScore = $playerRow['score'];
        $currentPlayerTime = $playerRow['timer'];

        $sqlRank = "
            SELECT COUNT(*)+1 AS rank
            FROM players
            WHERE status='finished'
              AND (score > " . (int)$playerRow['score'] . " OR (score = " . (int)$playerRow['score'] . " AND TIMESTAMPDIFF(SECOND,start_time,IFNULL(end_time,NOW())) < " . (int)$playerRow['timer'] . "))";
        $rankRes = $conn->query($sqlRank);
        $currentPlayerRank = $rankRes->fetch_assoc()['rank'];
    }
}

$conn->close();
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
            background: #000 url('../Animations/Homepage.mp4') center/cover no-repeat;
            /* fallback */
        }

        .background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            object-fit: cover;
            z-index: -1;
            background: #000;
            /* fallback fill */
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
            background: rgba(255, 255, 255, 0.85);
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
            font-size: 0.9rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 0.25rem;
        }

        .current-player-rank {
            margin-top: 1rem;
            border-top: 2px dashed #000;
            padding-top: 0.75rem;
            background: rgba(255, 255, 255, 0.85);
        }

        /* Play Again button at bottom-right of page */
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

    <video class="background-video" autoplay loop playsinline preload="auto" onloadeddata="this.classList.add('loaded')">
        <source src="../Animations/Homepage.mp4" type="video/mp4" />
    </video>

    <div class="leaderboard-container">
        <h2>Leaderboard</h2>

        <div class="leaderboard-scroll">
            <?php if (!empty($leaderboard)) : ?>
                <?php foreach ($leaderboard as $player) : ?>
                    <div class="player-card <?php echo (isset($_SESSION['player_name']) && $_SESSION['player_name'] === $player['name']) ? 'highlight' : ''; ?>">
                        <div>
                            <div class="field-title">Rank</div>
                            <?php echo $player['rank']; ?>
                        </div>
                        <div>
                            <div class="field-title">Name</div>
                            <?php echo htmlspecialchars($player['name']); ?>
                        </div>
                        <div>
                            <div class="field-title">Score</div>
                            <?php echo $player['score']; ?>
                        </div>
                        <div>
                            <div class="field-title">Time (s)</div>
                            <?php echo $player['timer']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#fff;">No players yet</p>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['player_name']) && $currentPlayerRank !== null) : ?>
            <div class="player-card current-player-rank highlight">
                <div>
                    <div class="field-title">Rank</div>
                    <?php echo $currentPlayerRank; ?>
                </div>
                <div>
                    <div class="field-title">Name</div>
                    <?php echo htmlspecialchars($_SESSION['player_name']); ?>
                </div>
                <div>
                    <div class="field-title">Score</div>
                    <?php echo $currentPlayerScore; ?>
                </div>
                <div>
                    <div class="field-title">Time (s)</div>
                    <?php echo $currentPlayerTime; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Play Again button outside leaderboard at bottom-right -->
    <button class="play-again-btn" onclick="window.location.href='../Pages/Homepage.html'">Play Again</button>

</body>

</html>