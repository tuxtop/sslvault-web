# sslvault-web
Web Interface for SSLVault

## Goal
SSLVault is a lightweight web tool to store and follow orders about SSL Certificates.

## Prerequisite
* PHP 5.6 with OpenSSL enabled
* PHP PDO with PostgreSQL driver

## Security advise
As this tool stores critical items, it is strongly advisable to limit access to the tool in a private network and to set an access to the PostgreSQL through a SSL connection only on the local server (if both database and application are installed on the same server).
