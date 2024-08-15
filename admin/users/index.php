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

// API!
$file_path = 'https://api.gymoneglobal.com/latest/version.txt';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $file_path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$latest_version = curl_exec($ch);
curl_close($ch);

$current_version = $version;

$is_new_version_available = version_compare($latest_version, $current_version) > 0;

$per_page = 10;
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $page = $_GET['page'];
} else {
    $page = 1;
}

$start_from = ($page - 1) * $per_page;

$search_name = isset($_GET['search_name']) ? $_GET['search_name'] : '';
$search_email = isset($_GET['search_email']) ? $_GET['search_email'] : '';

$sql = "SELECT * FROM users";

if (!empty($search_name) || !empty($search_email)) {
    $sql .= " WHERE ";
    $conditions = array();
    if (!empty($search_name)) {
        $conditions[] = "firstname LIKE '%$search_name%' OR lastname LIKE '%$search_name%'";
    }
    if (!empty($search_email)) {
        $conditions[] = "email LIKE '%$search_email%'";
    }
    $sql .= implode(" AND ", $conditions);
}

$sql .= " LIMIT $start_from, $per_page";

$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>">

<head>
    <meta charset="UTF-8">
    <title><?php echo $translations["dashboard"]; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
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
                    <li><a href="#">Geo</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row content">
            <div class="col-sm-2 sidenav hidden-xs text-center">
                <h2><img src="../../assets/img/logo.png" width="105px" alt="Logo"></h2>
                <p class="lead mb-4 fs-4"><?php echo $business_name ?> - <?php echo $version; ?></p>
                <ul class="nav nav-pills nav-stacked">
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="#">
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
                                <a class="sidebar-link" href="../boss/mainsettings">
                                    <i class="bi bi-gear"></i>
                                    <span><?php echo $translations["businesspage"]; ?></span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="../boss/workers">
                                    <i class="bi bi-people"></i>
                                    <span><?php echo $translations["workers"]; ?></span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="../boss/packages">
                                    <i class="bi bi-box-seam"></i>
                                    <span><?php echo $translations["packagepage"]; ?></span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="../boss/hours">
                                    <i class="bi bi-clock"></i>
                                    <span><?php echo $translations["openhourspage"]; ?></span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="../boss/smtp">
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
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="../boss/smtp">
                            <i class="bi bi-envelope-at"></i>
                            <span>felhasznalok kezelese</span>
                        </a>
                    </li>
                    <li><a href="#section3">Geo</a></li>
                    <li class="sidebar-header"><?php echo $translations["other-header"]; ?></li>
                    <?php if ($is_boss == 1) {
                    ?>
                            <li class="sidebar-item active">
                                <a class="sidebar-ling" href="../updater">
                                    <i class="bi bi-cloud-download"></i>
                                    <span><?php echo $translations["updatepage"]; ?></span>
                                    <?php if ($is_new_version_available) : ?>
                                        <span class="sidebar-badge badge">
                                            <i class="bi bi-exclamation-circle"></i>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                    <?php
                        }
                    ?>
                    <li class="sidebar-item">
                        <a class="sidebar-ling" href="../log">
                            <i class="bi bi-clock-history"></i>
                            <span><?php echo $translations["logpage"]; ?></span>
                        </a>
                    </li>
                </ul><br>
            </div>
            <br>
            <div class="col-sm-10">
                <div class="d-none topnav d-sm-inline-block">
                    <a href="https://gymoneglobal.com/discord" class="btn btn-primary mx-1" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-question-circle"></i>
                        <?php echo $translations["support"]; ?>
                    </a>

                    <a href="https://gymoneglobal.com/docs" class="btn btn-danger" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-journals"></i>
                        <?php echo $translations["docs"]; ?>
                    </a>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#logoutModal">
                        <?php echo $translations["logout"]; ?>
                    </button>
                    <h5 id="clock" style="display: inline-block; margin-bottom: 0;"></h5>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card shadow">
                            <form method="GET" class="mb-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" placeholder="Név keresése" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" placeholder="Email cím keresése" name="search_email" value="<?php echo htmlspecialchars($search_email); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary"><?php echo $translations["search"]; ?></button>
                                    </div>
                                    <div class="col-md-2">
                                        <a href="index.php" class="btn btn-success"><?php echo $translations["resetbtn"]; ?></a>
                                    </div>
                                </div>
                            </form>

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo $translations["firstname"]; ?></th>
                                        <th><?php echo $translations["lastname"]; ?></th>
                                        <th><?php echo $translations["email"]; ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $row["firstname"] . "</td>";
                                            echo "<td>" . $row["lastname"] . "</td>";
                                            echo "<td>" . $row["email"];
                                            if ($row["confirmed"] == "No") {
                                                echo " <span class='text-danger bi bi-exclamation-triangle-fill' data-bs-toggle='tooltip' title='" . $translations["waitingconfirm"] . "'></span>";
                                            }
                                            echo "</td>";
                                            echo '<td><a class="btn btn-primary" href="edit/?user=' . $row["userid"] . '">' . $translations["profilesee"] . '</a></td>';
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4'>No user data!</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>

                            <?php
                            $sql = "SELECT COUNT(*) AS total FROM users";
                            if (!empty($search_name) || !empty($search_email)) {
                                $sql .= " WHERE ";
                                $conditions = array();
                                if (!empty($search_name)) {
                                    $conditions[] = "firstname LIKE '%$search_name%' OR lastname LIKE '%$search_name%'";
                                }
                                if (!empty($search_email)) {
                                    $conditions[] = "email LIKE '%$search_email%'";
                                }
                                $sql .= implode(" AND ", $conditions);
                            }

                            $result = $conn->query($sql);
                            $row = $result->fetch_assoc();
                            $total_pages = ceil($row["total"] / $per_page);

                            if ($total_pages > 1) {
                                echo "<ul class='pagination justify-content-center'>";
                                for ($i = 1; $i <= $total_pages; $i++) {
                                    echo "<li class='page-item'><a class='page-link' href='?page=$i";
                                    if (!empty($search_name) || !empty($search_email)) {
                                        echo "&search_name=" . urlencode($search_name) . "&search_email=" . urlencode($search_email);
                                    }
                                    echo "'>$i</a></li>";
                                }
                                echo "</ul>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- EXIT MODAL -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <p class="lead"><?php echo $translations["exit-modal"]; ?></p>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo $translations["not-yet"]; ?></a>
                    <a href="../logout.php" type="button" class="btn btn-danger"><?php echo $translations["confirm"]; ?></a>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS! -->
    <script src="../../assets/js/date-time.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>

</html>