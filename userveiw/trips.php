<?php
session_start();
include 'header.php';
include 'checkinguserindb.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$month_filter = $_GET['month'] ?? '';
$date_filter = $_GET['date'] ?? '';

$sql = "
SELECT 
  ts.sessionid,
  ts.createdat as date,
  ts.fareamount as amount,
  ts.status,
  r.routename,
  b.platenumber,
  s1.stagename as startpoint,
  s2.stagename as endpoint
FROM tripsessions ts
JOIN trips t ON ts.tripid = t.tripid
JOIN routes r ON t.routeid = r.routeid
JOIN buses b ON t.busid = b.busid
JOIN stages s1 ON ts.fromstageid = s1.stageid
JOIN stages s2 ON ts.tostageid = s2.stageid
WHERE ts.userid = ?
";

$params = [$_SESSION['user_id']];
$types = "i";

if ($status_filter) {
    $sql .= " AND ts.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($month_filter) {
    $sql .= " AND DATE_FORMAT(ts.createdat, '%Y-%m') = ?";
    $params[] = $month_filter;
    $types .= "s";
}

if ($date_filter) {
    $sql .= " AND DATE(ts.createdat) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$sql .= " ORDER BY ts.createdat DESC";

if (!$stmt = $conn->prepare($sql)) {
    die("SQL ERROR: " . $conn->error);
}

$stmt->bind_param($types, ...$params);
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
  <link rel="stylesheet" href="darkmode.css">
  <script src="darkmode.js"></script>
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
    <form method="GET" style="display: flex; gap: 15px;">
        <select name="status" onchange="this.form.submit()">
          <option value="">All Status</option>
          <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
          <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>

        <input type="month" name="month" value="<?php echo $month_filter; ?>" onchange="this.form.submit()">
        <input type="date" name="date" value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
        <a href="trips.php" class="btn-reset" style="padding: 10px; color: #9ca3af; text-decoration: none;">Reset</a>
    </form>
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
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($trip = $result->fetch_assoc()): ?>
<tr>
  <td data-label="Date"><?= htmlspecialchars($trip['date']) ?></td>
  <td data-label="Route"><?= htmlspecialchars($trip['startpoint']." → ".$trip['endpoint']) ?></td>
  <td data-label="Vehicle"><?= htmlspecialchars($trip['platenumber']) ?></td>
  <td data-label="Amount">KES <?= htmlspecialchars($trip['amount']) ?></td>
  <td data-label="Status">
    <span class="status <?= $trip['status'] ?>">
      <?= ucfirst($trip['status']) ?>
    </span>
  </td>
  <td>
    <div class="trip-actions">
        <?php if ($trip['status'] == 'pending'): ?>
            <a href="initiate_payment.php?session=<?= $trip['sessionid'] ?>" class="action-btn pay">Pay Now</a>
            <a href="cancel_trip.php?session=<?= $trip['sessionid'] ?>" 
               class="action-btn cancel" 
               onclick="return confirm('Release seat and cancel this trip?')">Cancel</a>
        <?php else: ?>
            <span style="color: #64748b; font-size: 0.8rem;">No actions</span>
        <?php endif; ?>
    </div>
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