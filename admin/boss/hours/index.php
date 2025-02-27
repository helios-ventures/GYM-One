<?php
session_start();

if (!isset($_SESSION['adminuser'])) {
    header("Location: ../");
    exit();
}

$userid = $_SESSION['adminuser'];

function read_env_file($file_path)
{
    $env_file = file_get_contents($file_path);
    $env_lines = explode("\n", $env_file);
    $env_data = [];

    foreach ($env_lines as $line) {
        $line_parts = explode('=', $line);
        if (count($line_parts) == 2) {
            $key = trim($line_parts[0]);
            $value = trim($line_parts[1]);
            $env_data[$key] = $value;
        }
    }

    return $env_data;
}

$env_data = read_env_file('../../../.env');

$db_host = $env_data['DB_SERVER'] ?? '';
$db_username = $env_data['DB_USERNAME'] ?? '';
$db_password = $env_data['DB_PASSWORD'] ?? '';
$db_name = $env_data['DB_NAME'] ?? '';

$business_name = $env_data['BUSINESS_NAME'] ?? '';
$lang_code = $env_data['LANG_CODE'] ?? '';
$version = $env_data["APP_VERSION"] ?? '';

$lang = $lang_code;

$langDir = __DIR__ . "/../../../assets/lang/";

$langFile = $langDir . "$lang.json";

if (!file_exists($langFile)) {
    die("A nyelvi fájl nem található: $langFile");
}

$translations = json_decode(file_get_contents($langFile), true);

$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($conn->connect_error) {
    die("Kapcsolódási hiba: " . $conn->connect_error);
}

$sql = "SELECT is_boss FROM workers WHERE userid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$stmt->store_result();

$is_boss = null;

if ($stmt->num_rows > 0) {
    $stmt->bind_result($is_boss);
    $stmt->fetch();
}
$stmt->close();

$alerts_html = '';

$sql = "SELECT userid, Firstname, Lastname, username, is_boss FROM workers";
$result = $conn->query($sql);

$dayNames = [
    1 => $translations["Mon"],
    2 => $translations["Tue"],
    3 => $translations["Wed"],
    4 => $translations["Thu"],
    5 => $translations["Fri"],
    6 => $translations["Sat"],
    7 => $translations["Sun"]
];

$sql = "SELECT * FROM opening_hours ORDER BY day ASC";
$result = $conn->query($sql);

$days = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $days[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($dayNames as $dayNumber => $dayName) {
        $isClosed = isset($_POST['closed'][$dayNumber]) ? 1 : 0;

        if ($isClosed) {
            $open_time = 'NULL';
            $close_time = 'NULL';
        } else {
            $open_time = isset($_POST['open_time'][$dayNumber]) && !empty($_POST['open_time'][$dayNumber])
                ? "'" . $conn->real_escape_string($_POST['open_time'][$dayNumber]) . "'"
                : 'NULL';
            $close_time = isset($_POST['close_time'][$dayNumber]) && !empty($_POST['close_time'][$dayNumber])
                ? "'" . $conn->real_escape_string($_POST['close_time'][$dayNumber]) . "'"
                : 'NULL';
        }

        $updateSql = "UPDATE opening_hours SET open_time = $open_time, close_time = $close_time WHERE day = $dayNumber";
        if (!$conn->query($updateSql)) {
            echo "Hiba a(z) $dayName frissítésekor: " . $conn->error;
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$file_path = 'https://api.gymoneglobal.com/latest/version.txt';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $file_path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$latest_version = curl_exec($ch);
curl_close($ch);

$current_version = $version;

$is_new_version_available = version_compare($latest_version, $current_version) > 0;

$conn->close();
?>


<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>">

<head>
    <meta charset="UTF-8">
    <title><?php echo $translations["dashboard"]; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../../assets/css/dashboard.css">
    <link rel="shortcut icon" href="https://gymoneglobal.com/assets/img/logo.png" type="image/x-icon">
</head>
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<body>
    <nav class="navbar navbar-inverse visible-xs">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#"><img src="../../assets/img/logo.png" width="105px" alt="Logo"></a>
            </div>
            <div class="collapse navbar-collapse" id="myNavbar">
                <ul class="nav navbar-nav">
                    <li class="active"><a href="#"><?php echo $translations["mainpage"]; ?></a></li>
                    <li><a href="#">Age</a></li>
                    <li><a href="#">Gender</a></li>
                    <li><a href="#"><?php echo $_SESSION["userid"]; ?></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row content">
            <div class="col-sm-2 sidenav hidden-xs text-center">
                <h2><img src="../../../assets/img/logo.png" width="105px" alt="Logo"></h2>
                <p class="lead mb-4 fs-4"><?php echo $business_name ?> - <?php echo $version; ?></p>
                <ul class="nav nav-pills nav-stacked">
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="../../dashboard">
                            <i class="bi bi-speedometer"></i> <?php echo $translations["mainpage"]; ?>
                        </a>
                    </li>
                    <?php
                    if ($is_boss == 1) {
                    ?>
                        <li class="sidebar-header">
                            <?php echo $translations["settings"]; ?>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="../workers">
                                <i class="bi bi-people"></i>
                                <span><?php echo $translations["workers"]; ?></span>
                            </a>
                        </li>
                        <li class="sidebar-item active">
                            <a class="sidebar-link" href="#">
                                <i class="bi bi-clock"></i>
                                <span><?php echo $translations["openhourspage"]; ?></span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="../smtp">
                                <i class="bi bi-envelope-at"></i>
                                <span><?php echo $translations["mailpage"]; ?></span>
                            </a>
                        </li>
                    <?php
                    }
                    ?>
                    <li class="sidebar-header">
                        Bolt
                    </li>
                    <li><a href="#section3">Gender</a></li>
                    <li><a href="#section3">Geo</a></li>
                    <li class="sidebar-header"><?php echo $translations["other-header"]; ?></li>
                    <li class="sidebar-item">
                        <a class="sidebar-ling" href="../../log">
                            <i class="bi bi-clock-history"></i>
                            <span><?php echo $translations["logpage"]; ?></span>
                        </a>
                    </li>
                </ul><br>
            </div>

            <br>
            <div class="col-sm-10">
                <div class="d-none topnav d-sm-inline-block">
                    <a href="https://gymoneglobal.com/discord" class="btn btn-primary mx-1" target="_blank"
                        rel="noopener noreferrer">
                        <i class="bi bi-question-circle"></i>
                        <?php echo $translations["support"]; ?>
                    </a>

                    <a href="https://gymoneglobal.com/docs" class="btn btn-danger" target="_blank"
                        rel="noopener noreferrer">
                        <i class="bi bi-journals"></i>
                        <?php echo $translations["docs"]; ?>
                    </a>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#logoutModal">
                        <?php echo $translations["logout"]; ?>
                    </button>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <?php echo $alerts_html; ?>
                        <div class="card shadow">
                            <div class="card-body">

                                <?php
                                if ($is_boss == 1) {
                                ?>
                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $translations["day"]; ?></th>
                                                    <th><?php echo $translations["opentime"]; ?></th>
                                                    <th><?php echo $translations["closetime"]; ?></th>
                                                    <th><?php echo $translations["checkbox-closed"];?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($days as $day): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($dayNames[$day['day']]) ?></td>
                                                        <td>
                                                            <input type="time" name="open_time[<?= $day['day'] ?>]" value="<?= htmlspecialchars($day['open_time']) ?>" class="form-control" <?= is_null($day['open_time']) ? 'disabled' : '' ?>>
                                                        </td>
                                                        <td>
                                                            <input type="time" name="close_time[<?= $day['day'] ?>]" value="<?= htmlspecialchars($day['close_time']) ?>" class="form-control" <?= is_null($day['close_time']) ? 'disabled' : '' ?>>
                                                        </td>
                                                        <td class="text-center">
                                                            <input type="checkbox" name="closed[<?= $day['day'] ?>]" value="1" <?= is_null($day['open_time']) && is_null($day['close_time']) ? 'checked' : '' ?> onclick="toggleTimeFields(this, <?= $day['day'] ?>)">
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <button type="submit" class="btn btn-primary"><?= $translations["save"];?></button>

                                    </form>
                                <?php
                                } else {
                                    echo $translations["dont-access"];
                                }
                                ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <p><?php echo $translations["exit-modal"]; ?></p>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-secondary"
                        data-dismiss="modal"><?php echo $translations["not-yet"]; ?></a>
                    <a href="../logout.php" type="button"
                        class="btn btn-danger"><?php echo $translations["confirm"]; ?></a>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS! -->
    <script>
        function toggleTimeFields(checkbox, dayNumber) {
            const openTime = document.querySelector(`input[name="open_time[${dayNumber}]"]`);
            const closeTime = document.querySelector(`input[name="close_time[${dayNumber}]"]`);
            if (checkbox.checked) {
                openTime.value = '';
                closeTime.value = '';
                openTime.disabled = true;
                closeTime.disabled = true;
            } else {
                openTime.disabled = false;
                closeTime.disabled = false;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name^="closed"]');
            checkboxes.forEach(checkbox => {
                const dayNumber = checkbox.name.match(/\d+/)[0];
                toggleTimeFields(checkbox, dayNumber);
            });
        });
    </script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>

</html>