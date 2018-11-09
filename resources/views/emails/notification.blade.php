<html>
<head>
    <title>GPS System Notice</title>
</head>
<body>
    <p>License plate: <?php echo isset($data['license_plate']) ? $data['license_plate'] : '-';?></p>
    <p>Time: <?php echo isset($data['device_time']) ? $data['device_time'] : '-';?></p>
    <br>
    <br>
    <p><?php echo isset($data['alert_status']) ? $data['alert_status'] : '-';  ?> --- </p>
    <p>
        <a href="<?php echo 'https://www.google.co.id/maps/place/'.$data['latitude'].','.$data['longitude']; ?>">
            <?php echo "https://www.google.co.id/maps/place/".$data['latitude'].",".$data['longitude']; ?>
        </a>
    </p>
</body>
</html>