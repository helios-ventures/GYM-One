<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['adminuser'])) {
    header("Location: ../");
    exit();
}

$userid = $_SESSION['adminuser'];

$alerts_html = "";

function read_env_file($file_path)
{
    if (!file_exists($file_path)) {
        die("A .env fájl nem található: $file_path");
    }
    $env_file = file_get_contents($file_path);
    $env_lines = explode("\n", $env_file);
    $env_data = [];

    foreach ($env_lines as $line) {
        $line_parts = explode('=', $line, 2);
        if (count($line_parts) == 2) {
            $key = trim($line_parts[0]);
            $value = trim($line_parts[1]);
            $env_data[$key] = $value;
        }
    }

    return $env_data;
}

$env_data = read_env_file('../../.env');

$db_host = $env_data['DB_SERVER'] ?? '';
$db_username = $env_data['DB_USERNAME'] ?? '';
$db_password = $env_data['DB_PASSWORD'] ?? '';
$db_name = $env_data['DB_NAME'] ?? '';

$business_name = $env_data['BUSINESS_NAME'] ?? '';
$lang_code = $env_data['LANG_CODE'] ?? '';
$version = $env_data["APP_VERSION"] ?? '';

$lang = $lang_code;
$langDir = __DIR__ . "/../../assets/lang/";
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

$limit = 13;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT logs.id, workers.username as username, logs.action, logs.actioncolor, logs.time 
        FROM logs 
        JOIN workers ON logs.userid = workers.userid 
        ORDER BY logs.time DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if (isset($_POST['delete_old_logs'])) {
    $date_limit = date('Y-m-d', strtotime('-15 days'));

    $sql = "DELETE FROM logs WHERE time < ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date_limit);
    if ($stmt->execute()) {
        $delete_message = $translations["success-log-delete"];
        $action = $translations['success-log-delete'];
        $actioncolor = 'warning';
        $sql = "INSERT INTO logs (userid, action, actioncolor, time) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $userid, $action, $actioncolor);
        $stmt->execute();
        header("Refresh:2");
    } else {
        $delete_message = "An error occurred during the deletion: " . $conn->error;
        header("Refresh:2");
    }
}

// Get total number of logs for pagination
$total_sql = "SELECT COUNT(*) as total FROM logs";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_logs = $total_row['total'];
$total_pages = ceil($total_logs / $limit);

$username = 'mayerbalintdev';
$repo = 'GYM-One';
$current_version = $version;

$file_path = 'https://api.gymoneglobal.com/latest/version.txt';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $file_path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$latest_version = curl_exec($ch);
curl_close($ch);

$is_new_version_available = version_compare($latest_version, $current_version) > 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang_code, ENT_QUOTES, 'UTF-8'); ?>">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($translations["dashboard"], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="shortcut icon" href="https://gymoneglobal.com/assets/img/logo.png" type="image/x-icon">
</head>
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
                    <li class="active"><a href="#"><?php echo htmlspecialchars($translations["mainpage"], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li><a href="#">Age</a></li>
                    <li><a href="#">Gender</a></li>
                    <li><a href="#"><?php echo htmlspecialchars($_SESSION["adminuser"], ENT_QUOTES, 'UTF-8'); ?></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row content">
            <div class="col-sm-2 sidenav hidden-xs text-center">
                <h2><img src="../../assets/img/logo.png" width="105px" alt="Logo"></h2>
                <p class="lead mb-4 fs-4"><?php echo htmlspecialchars($business_name, ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($version, ENT_QUOTES, 'UTF-8'); ?></p>
                <ul class="nav nav-pills nav-stacked">
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="../dashboard">
                            <i class="bi bi-speedometer"></i> <?php echo htmlspecialchars($translations["mainpage"], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                    <?php if ($is_boss == 1): ?>
                        <li class="sidebar-header">
                            <?php echo htmlspecialchars($translations["settings"], ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="../boss/workers">
                                <i class="bi bi-people"></i>
                                <span><?php echo htmlspecialchars($translations["workers"], ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="../boss/hours">
                                <i class="bi bi-clock"></i>
                                <span><?php echo htmlspecialchars($translations["openhourspage"], ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="../boss/smtp">
                                <i class="bi bi-envelope-at"></i>
                                <span><?php echo htmlspecialchars($translations["mailpage"], ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="sidebar-header">Bolt</li>
                    <li><a href="#section3">Gender</a></li>
                    <li><a href="#section3">Geo</a></li>
                    <li class="sidebar-header"><?php echo htmlspecialchars($translations["other-header"], ENT_QUOTES, 'UTF-8'); ?></li>
                    <li class="sidebar-item active">
                        <a class="sidebar-link" href="#">
                            <i class="bi bi-clock-history"></i>
                            <span><?php echo htmlspecialchars($translations["logpage"], ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </li>
                </ul><br>
            </div>

            <div class="col-sm-10">
                <div class="d-none topnav d-sm-inline-block">
                    <a href="https://gymoneglobal.com/discord" class="btn btn-primary mx-1" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-question-circle"></i>
                        <?php echo htmlspecialchars($translations["support"], ENT_QUOTES, 'UTF-8'); ?>
                    </a>

                    <a href="https://gymoneglobal.com/docs" class="btn btn-danger" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-journals"></i>
                        <?php echo htmlspecialchars($translations["docs"], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#logoutModal">
                        <?php echo htmlspecialchars($translations["logout"], ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <h5 id="clock" style="display: inline-block; margin-bottom: 0;"></h5>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <?php echo $alerts_html; ?>
                        <div class="card shadow">
                            <div class="card-body">
                                <?php if (isset($delete_message)) : ?>
                                    <div class="alert alert-info"><?php echo htmlspecialchars($delete_message, ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                                <div class="table-responsive table-striped">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th><?php echo htmlspecialchars($translations["username"], ENT_QUOTES, 'UTF-8'); ?></th>
                                                <th><?php echo htmlspecialchars($translations["action-log"], ENT_QUOTES, 'UTF-8'); ?></th>
                                                <th><?php echo htmlspecialchars($translations["date-log"], ENT_QUOTES, 'UTF-8'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    echo "<tr>";
                                                    echo "<td><b>" . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . "</b></td>";
                                                    echo "<td>" . htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') . "</td>";
                                                    echo "<td class='text-" . htmlspecialchars($row['actioncolor'], ENT_QUOTES, 'UTF-8') . "'><p>" . htmlspecialchars($row['action'], ENT_QUOTES, 'UTF-8') . "</p></td>";
                                                    echo "<td>" . htmlspecialchars($row['time'], ENT_QUOTES, 'UTF-8') . "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td class='text-center' colspan='4'>" . htmlspecialchars($translations["notexist-log"], ENT_QUOTES, 'UTF-8') . "</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <nav>
                                    <ul class="pagination justify-content-center">
                                        <?php
                                        for ($i = 1; $i <= $total_pages; $i++) {
                                            $active = $i == $page ? 'active' : '';
                                            echo "<li class='page-item $active'><a class='page-link' href='?page=$i'>$i</a></li>";
                                        }
                                        ?>
                                    </ul>
                                    <form method="POST">
                                        <button type="submit" name="delete_old_logs" class="btn btn-danger mb-3"><i class="bi bi-trash"></i><?php echo htmlspecialchars($translations["deletelog"], ENT_QUOTES, 'UTF-8'); ?></button>
                                    </form>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <p><?php echo htmlspecialchars($translations["exit-modal"], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo htmlspecialchars($translations["not-yet"], ENT_QUOTES, 'UTF-8'); ?></a>
                    <a href="../logout.php" type="button" class="btn btn-danger"><?php echo htmlspecialchars($translations["confirm"], ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS! -->
    <script src="../../assets/js/date-time.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html>
