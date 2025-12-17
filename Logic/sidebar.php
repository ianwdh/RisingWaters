<?php
session_start();
include "../Logic/db.php";

$username = $_SESSION['player_name'] ?? 'Player';
$avatar = "NormalMale";

if ($username !== "Player") {
  $stmt = $conn->prepare("SELECT profilepicture FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    if (!empty($row['profilepicture'])) {
      $avatar = $row['profilepicture'];
    }
  }
}
?>

<div id="sidebar">
  <div class="sidebar-header">
    <img
      id="profilePic"
      src="../ProfilePictures/<?php echo htmlspecialchars($avatar); ?>.png"
      alt="Profile Picture"
      style="cursor:pointer" />
    <h3 id="username"><?php echo htmlspecialchars($username); ?></h3>
  </div>

  <div class="sidebar-section">
    <label data-i18n="playerScoreLabel">Player Score</label>
    <p id="playerScore">0</p>
  </div>

  <button id="quitGameBtn" data-i18n="quitGame">Quit Game</button>
</div>

<div id="sidebarToggle">
  <i class="bi bi-gear-fill"></i>
</div>

<script>
  // Click avatar → edit profile
  document.getElementById("profilePic").onclick = () => {
    window.location.href = "../Pages/Edit_Profile.html";
  };
</script>