<?php

namespace App;

use SQLite3;

class Database
{
    private SQLite3 $db;

    public function __construct()
    {
        $this->db = new SQLite3(__DIR__ . "/../storage/database.sqlite");
        $this->initializeDatabase();
    }

    private function initializeDatabase(): void
    {
        $query = "CREATE TABLE IF NOT EXISTS wallet (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            balanceUsd REAL,
            holdings TEXT,
            transactions TEXT
        )";

        $this->db->exec($query);
    }

    public function loadWallet(): ?array
    {
        $query = "SELECT balanceUsd, holdings, transactions FROM wallet WHERE id = 1";
        $result = $this->db->querySingle($query, true);
        return $result ?: null;
    }

    public function saveWallet(float $balanceUsd, array $holdings, array $transactions): void
    {
        $holdingsJson = json_encode($holdings);
        $transactionsJson = json_encode($transactions);

        $stmt = $this->db->prepare("INSERT OR REPLACE INTO wallet (id, balanceUsd, holdings, transactions) 
                                    VALUES (1, :balanceUsd, :holdings, :transactions)");
        $stmt->bindValue(":balanceUsd", $balanceUsd, SQLITE3_FLOAT);
        $stmt->bindValue(":holdings", $holdingsJson, SQLITE3_TEXT);
        $stmt->bindValue(":transactions", $transactionsJson, SQLITE3_TEXT);
        $stmt->execute();
    }

    public function __destruct()
    {
        $this->db->close();
    }
}