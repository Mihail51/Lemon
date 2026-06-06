<?php
// Единая инициализация проекта

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}