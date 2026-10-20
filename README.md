<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## 勤怠管理アプリ

## 概要

このアプリは出勤退勤の管理が目的です

## 環境構築手順

- Dockerのビルドからマイグレーション、シーディングまでを記述する

## 使用技術(実行環境)

- Laravel 10.x
- PHP
- Mysql
- Docker
- Laravel Sail

## 実装・設定上の注意事項

提供されたBladeファイルの修正

提供されたBladeファイルを使用し、バックエンド処理と連携するために必要な修正を行っています。

主な修正内容は以下のとおりです。

- Controllerから渡される変数名と、Bladeで使用する変数名の統一
- Controllerが受け取るURLパラメータ名と、Bladeのリンクで使用するパラメータ名の統一

デザインやレイアウトの枠組みは維持し、勤怠管理機能を動作させるための修正を行っています。
本リポジトリのコードを使用して環境構築する際は、GitHubに登録されている修正後のBladeファイルを使用してください。

## ER図

< - - - 作成したER図の画像 - - - >

## URL

- 開発環境 : http://lacalhost/
