<?php

$json_file = file_get_contents("Json/attributes.json");

$json_file_date = json_decode($json_file, true);

function getSessionByCode($data, $sessionCode)
{
    foreach ($data['sessions'] as $session) {
        if ($session['sessionCode'] === $sessionCode) {
            return $session;
        }
    }

    return null;
}

function getSession($data, $sessionCode)
{
    if ($sessionCode) {
        $result = getSessionByCode($data, $sessionCode);

        if ($result) {
            return json_encode($result);
        } else {
            return json_encode(['error' => "Session with code $sessionCode not found."]);
        }

    } else {
        return json_encode(['error' => 'No sessionCode provided.']);
    }
}

function updateSessionByCode($data, $sessionCode, $lastChange, $measures, $parties): bool
{
    foreach ($data['sessions'] as &$session) {
        if ($session['sessionCode'] === $sessionCode) {
            $session['lastChange'] = $lastChange;
            $session['measures'] = $measures;
            $session['parties'] = $parties;

            save_data_to_file($data);

            return true;
        }
    }
    return false;
}

function updateValuePerParty($data, $sessionCode, $partyId, $newValue): bool
{
    foreach ($data['sessions'] as &$session) {
        if ($session['sessionCode'] === $sessionCode) {
            foreach ($session['measures'] as &$measure) {
                foreach ($measure['valuePerParty'] as &$valuePerParty) {
                    if ($valuePerParty['partyId'] === $partyId) {
                        $valuePerParty['value'] = $newValue;

                        save_data_to_file($data);

                        return true;
                    }
                }
            }
        }
    }
    return false;
}

function createSession($data, $sessionCode, $lastChange, $measures, $parties)
{
    $new_session = [
        "sessionCode" => $sessionCode,
        "lastChange" => time(),
        "measures" => $measures,
        "parties" => $parties
    ];

    $data['sessions'][] = $new_session;

    save_data_to_file($data);
}

function save_data_to_file($data)
{
    file_put_contents("Json/attributes.json", json_encode($data));
}

$sessionCode = $_GET['sessionCode'] ?? null;
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');

    $data = json_decode($json, true);

    if ($data === null) {
        echo json_encode(["error" => "Invalid JSON"]);
        exit;
    }

    $sessionCode = $data['sessionCode'] ?? null;
    $lastChange = $data['lastChange'] ?? null;
    $measures = $data['measures'] ?? [];
    $parties = $data['parties'] ?? [];

    createSession($json_file_date, $sessionCode, $lastChange, $measures, $parties);

    $response = [
        "sessionCode" => $sessionCode,
        "lastChange" => $lastChange,
        "measuresCount" => $measures,
        "partiesCount" => $parties,
    ];

    echo json_encode($response);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (!empty($_GET['party'])) {
        $json = file_get_contents('php://input');

        $data = json_decode($json, true);

        if ($data === null) {
            echo json_encode(["error" => "Invalid JSON"]);
            exit;
        }

        $sessionCode = $data['sessionCode'] ?? null;
        $lastChange = $data['lastChange'] ?? null;
        $measures = $data['measures'] ?? [];
        $parties = $data['parties'] ?? [];
        $newValue = $data['value'] ?? null;

        updateValuePerParty($json_file_date, $sessionCode, $_GET['party'], $newValue);

        $response = [
            "sessionCode" => $sessionCode,
            "lastChange" => $lastChange,
            "measuresCount" => $measures,
            "partiesCount" => $parties,
        ];

        echo json_encode($response);
    } else {
        $json = file_get_contents('php://input');

        $data = json_decode($json, true);

        if ($data === null) {
            echo json_encode(["error" => "Invalid JSON"]);
            exit;
        }

        $sessionCode = $data['sessionCode'] ?? null;
        $lastChange = $data['lastChange'] ?? null;
        $measures = $data['measures'] ?? [];
        $parties = $data['parties'] ?? [];

        updateSessionByCode($json_file_date, $sessionCode, $lastChange, $measures, $parties);

        $response = [
            "sessionCode" => $sessionCode,
            "lastChange" => $lastChange,
            "measuresCount" => $measures,
            "partiesCount" => $parties,
        ];

        echo json_encode($response);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo getSession($json_file_date, $sessionCode);
}
