<?php
session_start();
include 'header.php';
include 'checkinguserindb.php';

$sql = "
SELECT 
  trips.date,
  trips.amount,
  trips.status,
  routes.startpoint,
  routes.endpoint,
  buses.platenumber
FROM trips
JOIN routes ON trips.routeid = routes.routeid  -- Changed route_id to routeid
JOIN buses ON trips.busid = buses.busid
WHERE trips.userid = ?
ORDER BY trips.date DESC
";
if (!$stmt = $conn->prepare($sql)) {
    die("SQL ERROR: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Trips | SafiriPay</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="trips.css">
</head>
<body>

<main class="trips-container">

  <!-- HEADER -->
  <section class="page-header">
    <h2>🚍 My Trips</h2>
    <p>Your travel history & payments</p>
  </section>

  <!-- FILTERS -->
  <section class="filter-bar">
    <select>
      <option value="">All Trips</option>
      <option value="completed">Completed</option>
      <option value="cancelled">Cancelled</option>
    </select>

    <input type="month">
  </section>

  <!-- TABLE -->
  <section class="table-wrapper">
    <table class="trips-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Route</th>
          <th>Vehicle</th>
          <th>Amount</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($trip = $result->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($trip['date']) ?></td>
  <td><?= htmlspecialchars($trip['startpoint']." → ".$trip['endpoint']) ?></td>
  <td><?= htmlspecialchars($trip['platenumber']) ?></td>
  <td>KES <?= htmlspecialchars($trip['amount']) ?></td>
  <td>
    <span class="status <?= $trip['status'] ?>">
      <?= ucfirst($trip['status']) ?>
    </span>
  </td>
</tr>
<?php endwhile; ?>


        <!-- <tr>
          <td>2026-01-22</td>
          <td>CBD → Westlands</td>
          <td>KCA 234A</td>
          <td>KES 120</td>
          <td><span class="status completed">Completed</span></td>
        </tr>

        <tr>
          <td>2026-01-20</td>
          <td>Rongai → CBD</td>
          <td>KDN 821M</td>
          <td>KES 150</td>
          <td><span class="status completed">Completed</span></td>
        </tr>

        <tr>
          <td>2026-01-18</td>
          <td>CBD → Kasarani</td>
          <td>KDG 112B</td>
          <td>KES 100</td>
          <td><span class="status cancelled">Cancelled</span></td>
        </tr> -->

      </tbody>
    </table>
  </section>

</main>

</body>
</html>
<?php
?>