<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../app-styles/app-style.css">
        
    <title>Mediasystem-v1</title>

</head>
<body>

<div id='header-info-panel'>

    <ul>
        <li><?php echo "<b>The root path: </b>".$_SERVER['DOCUMENT_ROOT']?></li>
        <li><?php echo "<b>Current Directory: </b>".$_SERVER['PHP_SELF']?></li>
        <li><?php echo "<b>Testing includes: </b>";$x = 1;if(isset($x)) {include ('../app-test/test_include.php');} else {echo "No includes detected.";}?></li>
        
        <li><?php ?></li>

    </ul>

</div>
