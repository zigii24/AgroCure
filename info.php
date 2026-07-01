<?php
echo "PHP Version: " . phpversion();
echo "<br>";
if (extension_loaded('mysqli')) {
    echo "mysqli is ENABLED";
} else {
    echo "mysqli is DISABLED";
}
?>