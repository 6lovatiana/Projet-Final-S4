<?php
$db = new SQLite3('writable/database.db');

echo "=== Clients ===\n";
$r = $db->query('SELECT id, numero, solde FROM clients ORDER BY id');
while ($row = $r->fetchArray(SQLITE3_ASSOC)) {
    echo $row['id'] . ' | ' . $row['numero'] . ' | ' . $row['solde'] . "\n";
}

echo "\n=== Transactions ===\n";
$r = $db->query('SELECT id, type_operation_id, client_id, client_destination_id, montant, frais, solde_avant, solde_apres FROM transactions ORDER BY id');
while ($row = $r->fetchArray(SQLITE3_ASSOC)) {
    echo json_encode($row) . "\n";
}
