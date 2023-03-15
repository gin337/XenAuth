<?php
ini_set('display_errors', 0); // We don't want to show any errors

header('Content-Type: application/json'); // Server should always return json

$config = json_decode(file_get_contents('/var/config/config.json'), true); // We get the Database Information

// We define a simple status response for the client
if (isset($_GET['status'])) { 
    sp(200, 'Ok');
}

// This is currently the endpoint i made for the auth 
if (isset($_GET['compare'])) {

    // Lets check if the client has sent all the required data. This is the simpliest approach. If not we return a 400

    if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['hwid'])) {
        sp(400, 'Bad Request');
    }

    // We define and execute our sql connection. If it fails we return a 500. We will actually reeuse this connection for all our functions.

    $conn = new mysqli($config['SQL_HOST'], $config['SQL_USER'], $config['SQL_PASS'], $config['SQL_DB']);
    if ($conn->connect_error) {
        sp(500, 'Something failed',);
    }

    // We now read the data sent from the client and store it in variables

    $username = $_POST['username'];
    $password = $_POST['password'];
    $hwid = $_POST['hwid'];

    // We now call our functions to check if the user exists

    $userid = GetId($conn, $username);

    if ($userid === null) {
        sp(404, 'Password wrong or user not found');
    }

    // The userid will return the unique key for the user. We search for the defined hash in the database and compare it with the password sent from the client. If it doesn't match we return a 404.

    $hash = GetHash($conn, $userid);

    if ($hash === null || !CompareHash($password, $hash)) {
        sp(404, 'Password wrong or user not found');
    }

    // For security reasons we now check if the user has a hwid stored in the database. If not we insert the one sent from the client. If it does we compare it with the one sent from the client. If it doesn't match we return a 403.

    $userhwid = GetHwid($conn, $userid);

    if ($userhwid === null) {
        InsertHwid($conn, $userid, $hwid);
        $data = FetchData($conn, $userid);
        sp(200, 'Ok', $data['id'], $data['email'], $data['group_id'], $data['avatar']);
    } elseif ($userhwid === $hwid) {
        $data = FetchData($conn, $userid);
        sp(200, 'Ok', $data['id'], $data['email'], $data['group_id'], $data['avatar']);
    } else {
        sp(403, 'hwid_missmatch');
    }

    // Dont forget to db connection we opened at the beginning. This will prevent injection attacks and other stuff.

    $conn->close();
}

// DRY ;) We define a simple function that returns a json response to the client. We can also use this function to return a 500 error if something fails.
function sp($code, $status = '', $id = null, $email = null, $group_id = null, $avatar = null) {
    http_response_code($code);
    $response = array(
        'status' => $status,
    );
    if ($id !== null && $email !== null && $group_id !== null && $avatar !== null) {
        $response['id'] = $id;
        $response['email'] = $email;
        $response['group_id'] = $group_id;
        $response['avatar'] = $avatar;
        
    }
    echo json_encode($response);
    die();
}


// Getting the user id from the username
function GetId($conn, $username)
{
    $stmt = $conn->prepare('SELECT user_id FROM xf_user WHERE username = ?');
    if (!$stmt) {
        sp(500, 'Something failed');
        die();
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userid = $row['user_id'];
        $stmt->close();
        return $userid;
    } else {
        $stmt->close();
        return null;
    }
}


// Getting the hash from the user id
function GetHash($conn, $userid)
{
    $stmt = $conn->prepare('SELECT data FROM xf_user_authenticate WHERE user_id = ?');
    if (!$stmt) {
        sp(500, 'Something failed');
        die();
    }

    $stmt->bind_param('i', $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hash = $row['data'];
        $stmt->close();
        return $hash;
    } else {
        $stmt->close();
        return null;
    }
}

// Comparing the hash with the sent password

function CompareHash($password, $hash)
{
    $array = unserialize($hash);
    $storedhash = $array['hash'];
    return password_verify($password, $storedhash);
}


// Getting the hwid from the user id
function GetHwid($conn, $userid)
{
    $stmt = $conn->prepare('SELECT hwid FROM xf_user_info WHERE user_id = ?');
    $stmt->bind_param('i', $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userhwid = $row['hwid'];
        $stmt->close();
        return $userhwid;
    } else {
        $stmt->close();
        return null;
    }
}

// Inserting the hwid into the database if it doesn't exist
function InsertHwid($conn, $userid, $hwid)
{
    $stmt = $conn->prepare('INSERT INTO xf_user_info (user_id, hwid) VALUES (?, ?)');
    $stmt->bind_param('is', $userid, $hwid);
    $stmt->execute();
    $stmt->close();
}


// Fetching the data we need to return to the client
function FetchData($conn, $userid)
{
    global $config;

    $stmt = $conn->prepare('SELECT user_id, email, user_group_id FROM xf_user WHERE user_id = ?');
    $stmt->bind_param('i', $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $data = array(
            'id' => $row['user_id'],
            'email' => $row['email'],
            'group_id' => $row['user_group_id'],
            'avatar' => $config['FORUM_URL'] . 'data/avatars/l/0/' . $row['user_id'] . '.jpg'
        );
        $stmt->close();
        return $data;
    } else {
        $stmt->close();
        return null;
    }
}
