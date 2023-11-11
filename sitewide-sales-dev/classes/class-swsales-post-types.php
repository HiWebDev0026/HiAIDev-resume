<?php

namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Post_Types {

	/**
	 * [init description]
	 *
	 * @return [type] [description]
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_sitewide_sale_cpt' ) );
		add_filter( 'manage_sitewide_sale_posts_columns', array( __CLASS__, 'set_sitewide_sale_columns' ) );
		add_action( 'manage_sitewide_sale_posts_custom_column', array( __CLASS__, 'fill_sitewide_sale_columns' ), 10, 2 );
		add_filter( 'months_dropdown_results', '__return_empty_array' );
		add_filter( 'post_row_actions', array( __CLASS__, 'remove_sitewide_sale_row_actions' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_swsales_set_active_sitewide_sale', array( __CLASS__, 'set_active_sitewide_sale' ) );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'force_publish_status' ), 10, 2 );
	}

	/**
	 * [register_sitewide_sale_cpt description]
	 *
	 * @return [type] [description]
	 */
	public static function register_sitewide_sale_cpt() {

		$menu_icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI2LjAuMywgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAxNDAgMTQwIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxNDAgMTQwOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+Cgkuc3Qwe2ZpbGw6I0ZGRkZGRjt9Cjwvc3R5bGU+CjxnPgoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTM3LjUsMTA3LjY5bC0xLjctMC4xOGMtMS43NC0wLjE5LTMuNDEtMC42OS00Ljk3LTEuNWwtMS41Mi0wLjc5bDEuNTgtMy4wM2wxLjUyLDAuNzkKCQljMS4xNywwLjYxLDIuNDMsMC45OSwzLjc1LDEuMTNsMS43LDAuMThMMzcuNSwxMDcuNjl6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTIyLjQ5LDEwNy42NWwtMC4xNi0zLjQybDEuNzEtMC4wOGMxLjMyLTAuMDYsMi42MS0wLjM3LDMuODEtMC45MWwxLjU2LTAuN2wxLjQsMy4xMmwtMS41NiwwLjcKCQljLTEuNTksMC43MS0zLjI5LDEuMTItNS4wNCwxLjJMMTIyLjQ5LDEwNy42NXoiLz4KCTxyZWN0IHg9IjExMi4xMSIgeT0iMTA0LjE3IiBjbGFzcz0ic3QwIiB3aWR0aD0iOCIgaGVpZ2h0PSIzLjQyIi8+Cgk8cmVjdCB4PSIxMDEuODEiIHk9IjEwNC4xNyIgY2xhc3M9InN0MCIgd2lkdGg9IjgiIGhlaWdodD0iMy40MiIvPgoJPHJlY3QgeD0iOTEuNSIgeT0iMTA0LjE3IiBjbGFzcz0ic3QwIiB3aWR0aD0iOCIgaGVpZ2h0PSIzLjQyIi8+Cgk8cmVjdCB4PSI4MS4yIiB5PSIxMDQuMTciIGNsYXNzPSJzdDAiIHdpZHRoPSI4IiBoZWlnaHQ9IjMuNDIiLz4KCTxyZWN0IHg9IjcwLjkiIHk9IjEwNC4xNyIgY2xhc3M9InN0MCIgd2lkdGg9IjgiIGhlaWdodD0iMy40MiIvPgoJPHJlY3QgeD0iNjAuNiIgeT0iMTA0LjE3IiBjbGFzcz0ic3QwIiB3aWR0aD0iOCIgaGVpZ2h0PSIzLjQyIi8+Cgk8cmVjdCB4PSI0MCIgeT0iMTA0LjE3IiBjbGFzcz0ic3QwIiB3aWR0aD0iOCIgaGVpZ2h0PSIzLjQyIi8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTMzLjI5LDEwMy43N2wtMi42NC0yLjE3bDEuMDgtMS4zMmMwLjg0LTEuMDMsMS40Ny0yLjE4LDEuODgtMy40NGwwLjUzLTEuNjNsMy4yNSwxLjA2bC0wLjUzLDEuNjMKCQljLTAuNTQsMS42Ni0xLjM4LDMuMTktMi40OSw0LjU1TDEzMy4yOSwxMDMuNzd6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMjYuOTYsMTAzLjE4bC0xLTEuMzhjLTEuMDMtMS40Mi0xLjc4LTMtMi4yMi00LjY5bC0wLjQzLTEuNjZsMy4zMS0wLjg2bDAuNDMsMS42NQoJCWMwLjMzLDEuMjgsMC45LDIuNDcsMS42NywzLjU0bDEuMDEsMS4zOEwyNi45NiwxMDMuMTh6Ii8+Cgk8cmVjdCB4PSIxMzQuMTMiIHk9Ijg1LjQzIiBjbGFzcz0ic3QwIiB3aWR0aD0iMy40MiIgaGVpZ2h0PSI4Ii8+Cgk8cmVjdCB4PSIyMy4yOCIgeT0iODQuNyIgY2xhc3M9InN0MCIgd2lkdGg9IjMuNDIiIGhlaWdodD0iMTIiLz4KCTxyZWN0IHg9IjEzNC4xMyIgeT0iNzUuMTIiIGNsYXNzPSJzdDAiIHdpZHRoPSIzLjQyIiBoZWlnaHQ9IjgiLz4KCTxyZWN0IHg9IjEzNC4xMyIgeT0iNjQuODIiIGNsYXNzPSJzdDAiIHdpZHRoPSIzLjQyIiBoZWlnaHQ9IjgiLz4KCTxyZWN0IHg9IjIzLjI4IiB5PSI2NC4wOSIgY2xhc3M9InN0MCIgd2lkdGg9IjMuNDIiIGhlaWdodD0iOCIvPgoJPHJlY3QgeD0iMTM0LjEzIiB5PSI1NC41MiIgY2xhc3M9InN0MCIgd2lkdGg9IjMuNDIiIGhlaWdodD0iOCIvPgoJPHJlY3QgeD0iMjMuMjgiIHk9IjUzLjc5IiBjbGFzcz0ic3QwIiB3aWR0aD0iMy40MiIgaGVpZ2h0PSI4Ii8+Cgk8cmVjdCB4PSIxMzQuMTMiIHk9IjQ0LjIyIiBjbGFzcz0ic3QwIiB3aWR0aD0iMy40MiIgaGVpZ2h0PSI4Ii8+Cgk8cmVjdCB4PSIyMy4yOCIgeT0iNDMuNDkiIGNsYXNzPSJzdDAiIHdpZHRoPSIzLjQyIiBoZWlnaHQ9IjgiLz4KCTxyZWN0IHg9IjEzNC4xMyIgeT0iMzMuOTIiIGNsYXNzPSJzdDAiIHdpZHRoPSIzLjQyIiBoZWlnaHQ9IjgiLz4KCTxyZWN0IHg9IjIzLjI4IiB5PSIzMy4xOSIgY2xhc3M9InN0MCIgd2lkdGg9IjMuNDIiIGhlaWdodD0iOCIvPgoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTEzNC4xMywzMC41N3YtMi42OWMwLTAuNzQtMC4wOC0xLjQ3LTAuMjItMi4xOGwtMC4zNS0xLjY3bDMuMzUtMC43bDAuMzUsMS42N2MwLjIsMC45NCwwLjMsMS45MSwwLjMsMi44OAoJCXYyLjY5SDEzNC4xM3oiLz4KCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0yNi43LDMwLjg4aC0zLjQydi0zLjAxYzAtMS4yNSwwLjE3LTIuNDksMC40OS0zLjY5bDAuNDUtMS42NWwzLjMsMC45bC0wLjQ1LDEuNjUKCQljLTAuMjUsMC45LTAuMzcsMS44NC0wLjM3LDIuNzlWMzAuODh6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTMyLjg5LDIyLjYybC0xLjA3LTEuMzRjLTAuODMtMS4wNC0xLjgyLTEuOS0yLjk2LTIuNTdsLTEuNDgtMC44NmwxLjczLTIuOTVsMS40OCwwLjg2CgkJYzEuNTEsMC44OCwyLjgzLDIuMDIsMy45MiwzLjM5bDEuMDcsMS4zNEwxMzIuODksMjIuNjJ6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMjguMjcsMjIuMDdsLTIuNTUtMi4yOGwxLjE0LTEuMjdjMS4xNy0xLjMsMi41NS0yLjM3LDQuMTEtMy4xNmwxLjUzLTAuNzdsMS41NSwzLjA1bC0xLjUzLDAuNzcKCQljLTEuMTgsMC42LTIuMjIsMS40LTMuMTEsMi4zOUwyOC4yNywyMi4wN3oiLz4KCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xMjUuOSwxNy40bC0xLjcxLTAuMTFjLTAuMjItMC4wMS0wLjQ1LTAuMDItMC42Ny0wLjAyaC01LjUxdi0zLjQyaDUuNTFjMC4zLDAsMC41OSwwLjAxLDAuODksMC4wM2wxLjcxLDAuMTEKCQlMMTI1LjksMTcuNHoiLz4KCTxyZWN0IHg9IjEwNy43MSIgeT0iMTMuODUiIGNsYXNzPSJzdDAiIHdpZHRoPSI4IiBoZWlnaHQ9IjMuNDIiLz4KCTxyZWN0IHg9Ijk3LjQxIiB5PSIxMy44NSIgY2xhc3M9InN0MCIgd2lkdGg9IjgiIGhlaWdodD0iMy40MiIvPgoJPHJlY3QgeD0iODcuMTEiIHk9IjEzLjg1IiBjbGFzcz0ic3QwIiB3aWR0aD0iOCIgaGVpZ2h0PSIzLjQyIi8+Cgk8cmVjdCB4PSI3Ni44MSIgeT0iMTMuODUiIGNsYXNzPSJzdDAiIHdpZHRoPSI4IiBoZWlnaHQ9IjMuNDIiLz4KCTxyZWN0IHg9IjY2LjUiIHk9IjEzLjg1IiBjbGFzcz0ic3QwIiB3aWR0aD0iOCIgaGVpZ2h0PSIzLjQyIi8+Cgk8cmVjdCB4PSI1Ni4yIiB5PSIxMy44NSIgY2xhc3M9InN0MCIgd2lkdGg9IjgiIGhlaWdodD0iMy40MiIvPgoJPHJlY3QgeD0iNDUuOSIgeT0iMTMuODUiIGNsYXNzPSJzdDAiIHdpZHRoPSI4IiBoZWlnaHQ9IjMuNDIiLz4KCTxwb2x5Z29uIGNsYXNzPSJzdDAiIHBvaW50cz0iNDMuNiwxNy4yNyAzNS42LDE3LjI3IDM1LjU1LDEzLjg1IDM3LjI2LDEzLjg1IDM3LjI2LDE1LjU2IDM3LjI3LDEzLjg1IDQzLjYsMTMuODUgCSIvPgoJPHJlY3QgeD0iNDAuMyIgeT0iMTA0LjE3IiBjbGFzcz0ic3QwIiB3aWR0aD0iMTgiIGhlaWdodD0iMy40MiIvPgoJPHJlY3QgeD0iMjMuMjgiIHk9Ijc0LjM5IiBjbGFzcz0ic3QwIiB3aWR0aD0iMy40MiIgaGVpZ2h0PSI4Ii8+Cgk8Y2lyY2xlIGNsYXNzPSJzdDAiIGN4PSI2MC42IiBjeT0iMzkuNTYiIHI9IjguMjEiLz4KCTxjaXJjbGUgY2xhc3M9InN0MCIgY3g9IjEwMC4zNiIgY3k9IjgxLjQ1IiByPSIxMy41NSIvPgoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTEyMC41NiwyNy43MWMtMi42NywxLjQzLTUuMzIsMi44OS03Ljk1LDQuMzhjLTAuOTQsMC41My0xLjg3LDEuMDYtMi44MSwxLjZjLTAuOTMsMC41My0xLjg1LDEuMDctMi43OCwxLjYxCgkJYy0wLjkyLDAuNTQtMS44NCwxLjA5LTIuNzUsMS42M2MtMC45MSwwLjU0LTEuODIsMS4wOS0yLjcyLDEuNjRjLTAuNzksMC40OC0xLjU3LDAuOTYtMi4zNSwxLjQ0Yy0wLjc4LDAuNDgtMS41NSwwLjk2LTIuMzMsMS40NQoJCWMtMC43NywwLjQ4LTEuNTQsMC45Ny0yLjMxLDEuNDZjLTAuNzYsMC40OS0xLjUzLDAuOTgtMi4yOSwxLjQ3Yy0wLjUxLDAuMzMtMS4wMSwwLjY2LTEuNTIsMC45OWMtMC41LDAuMzMtMS4wMSwwLjY3LTEuNTEsMQoJCWMtMC41LDAuMzMtMSwwLjY3LTEuNSwxYy0wLjUsMC4zMy0wLjk5LDAuNjctMS40OSwxLjAxYy0wLjE3LDAuMTItMC4zNCwwLjIzLTAuNTEsMC4zNWMtMC4xNywwLjEyLTAuMzQsMC4yNC0wLjUxLDAuMzYKCQljLTAuMTcsMC4xMi0wLjM0LDAuMjMtMC41MSwwLjM1Yy0wLjE3LDAuMTItMC4zNCwwLjIzLTAuNTEsMC4zNWMtMC42MSwwLjQyLTEuMjIsMC44NS0xLjgyLDEuMjdjLTAuNiwwLjQyLTEuMiwwLjg1LTEuOCwxLjI4CgkJYy0wLjYsMC40My0xLjIsMC44NS0xLjc5LDEuMjljLTAuNTksMC40My0xLjE4LDAuODYtMS43OCwxLjI5Yy0zLjksMi44Ny03LjcxLDUuODEtMTEuNDEsOC44M2MtMy42NywyLjk5LTcuMjQsNi4wNi0xMC43LDkuMjIKCQljLTMuNDMsMy4xMi02Ljc2LDYuMzQtOS45OCw5LjY0Yy0zLjE5LDMuMjgtNi4yOCw2LjY1LTkuMjQsMTAuMTJjLTAuMjcsMC4zMS0wLjU0LDAuNjMtMC44MSwwLjk1Yy0wLjI3LDAuMzEtMC41MywwLjYzLTAuOCwwLjk1CgkJYy0wLjI2LDAuMzItMC41MywwLjY0LTAuNzksMC45NWMtMC4yNiwwLjMyLTAuNTIsMC42NC0wLjc4LDAuOTZjLTAuMiwwLjI0LTAuNCwwLjQ5LTAuNTksMC43M2MtMC4yLDAuMjUtMC40LDAuNDktMC41OSwwLjc0CgkJYy0wLjIsMC4yNS0wLjM5LDAuNDktMC41OSwwLjc0Yy0wLjE5LDAuMjUtMC4zOSwwLjUtMC41OCwwLjc0Yy0wLjExLTAuMTMtMC4yMS0wLjI1LTAuMzItMC4zOGMtMC4xMS0wLjEzLTAuMjEtMC4yNS0wLjMxLTAuMzcKCQljLTAuMS0wLjEyLTAuMjEtMC4yNC0wLjMxLTAuMzZjLTAuMS0wLjEyLTAuMi0wLjI0LTAuMy0wLjM2Yy0xLTEuMTktMS45NC0yLjI4LTIuODMtMy4yOWMtMC44OC0xLjAxLTEuNzEtMS45NC0yLjUxLTIuODIKCQljLTAuNzktMC44OC0xLjU1LTEuNy0yLjI4LTIuNWMtMC43My0wLjc5LTEuNDMtMS41NS0yLjEyLTIuM2MtMC4xOC0wLjE5LTAuMzYtMC4zOS0wLjU0LTAuNThjLTAuMTgtMC4xOS0wLjM2LTAuMzktMC41My0wLjU4CgkJYy0wLjE4LTAuMTktMC4zNS0wLjM5LTAuNTMtMC41OGMtMC4xNy0wLjE5LTAuMzUtMC4zOS0wLjUyLTAuNTljLTAuNzctMC44NC0xLjUzLTEuNy0yLjMxLTIuNTljLTAuNzgtMC44OS0xLjU3LTEuODItMi4zOS0yLjgxCgkJYy0wLjgxLTAuOTktMS42Ni0yLjAzLTIuNTUtMy4xN2MtMC44OC0xLjEyLTEuOC0yLjMzLTIuNzgtMy42NGMtMC40NC0wLjU5LTAuODktMS4yMS0xLjM2LTEuODRjLTAuNDYtMC42NC0wLjk0LTEuMjktMS40Mi0xLjk3CgkJYy0wLjQ4LTAuNjgtMC45OC0xLjM4LTEuNDktMi4xMWMtMC41MS0wLjcyLTEuMDMtMS40Ny0xLjU3LTIuMjVjMS4wNiwzLjQxLDIuMDYsNi42MiwzLjAzLDkuNjdjMC45OCwzLjEsMS45Myw2LjAzLDIuODQsOC44MgoJCUM4LDg1Ljc1LDguOSw4OC40Myw5Ljc3LDkxYzAuODgsMi42MSwxLjc1LDUuMSwyLjYxLDcuNTJjMC4yLDAuNTYsMC40LDEuMTIsMC42LDEuNjhjMC4yLDAuNTYsMC40LDEuMTEsMC42LDEuNjYKCQljMC4yLDAuNTUsMC40LDEuMSwwLjYsMS42NGMwLjIsMC41NSwwLjQsMS4wOSwwLjYsMS42NGMwLjA5LDAuMjUsMC4xOSwwLjUsMC4yOCwwLjc1YzAuMDksMC4yNSwwLjE5LDAuNSwwLjI4LDAuNzUKCQljMC4wOSwwLjI1LDAuMTksMC41LDAuMjgsMC43NWMwLjA5LDAuMjUsMC4xOSwwLjUxLDAuMjgsMC43NmMwLjEsMC4yNiwwLjE5LDAuNTIsMC4yOSwwLjc3YzAuMSwwLjI2LDAuMiwwLjUyLDAuMywwLjc4CgkJYzAuMSwwLjI2LDAuMiwwLjUyLDAuMywwLjc4YzAuMSwwLjI2LDAuMiwwLjUyLDAuMywwLjc4YzAuMDcsMC4xOSwwLjE0LDAuMzcsMC4yMSwwLjU2YzAuMDgsMC4xOSwwLjE0LDAuMzgsMC4yMiwwLjU2CgkJYzAuMDcsMC4xOSwwLjE1LDAuMzcsMC4yMiwwLjU2YzAuMDcsMC4xOSwwLjE1LDAuMzgsMC4yMiwwLjU3YzAuMDMsMC4wOCwwLjA2LDAuMTYsMC4wOSwwLjI0YzAuMDMsMC4wOCwwLjA2LDAuMTYsMC4wOSwwLjI0CgkJYzAuMDMsMC4wOCwwLjA2LDAuMTYsMC4wOSwwLjI0YzAuMDMsMC4wOCwwLjA2LDAuMTYsMC4wOSwwLjI0YzAuMSwwLjI2LDAuMiwwLjUxLDAuMywwLjc3YzAuMSwwLjI2LDAuMiwwLjUyLDAuMywwLjc3CgkJYzAuMSwwLjI2LDAuMiwwLjUyLDAuMzEsMC43OGMwLjEsMC4yNiwwLjIsMC41MiwwLjMsMC43OWwtMC4wNywwLjI2bDAuMTUtMC4wNmMwLjYxLDEuNTMsMS4yMywzLjA5LDEuODcsNC42OQoJCWMwLjY1LDEuNjEsMS4zMiwzLjI2LDIuMDEsNC45NmMwLjcsMS43MiwxLjQzLDMuNDgsMi4xOCw1LjMyYzAuNzcsMS44NiwxLjU3LDMuNzksMi40LDUuODJjMS40LTIuNTUsMi44My01LjA3LDQuMjktNy41NQoJCWMxLjQ2LTIuNDgsMi45NC00LjkzLDQuNDUtNy4zNGMxLjUxLTIuNDEsMy4wNS00LjgsNC42MS03LjE1YzEuNTctMi4zNSwzLjE2LTQuNjcsNC43Ny02Ljk2YzAuMDMtMC4wNSwwLjA2LTAuMDksMC4xLTAuMTQKCQljMC4wMy0wLjA1LDAuMDctMC4wOSwwLjEtMC4xNGMwLjA0LTAuMDQsMC4wNy0wLjA5LDAuMS0wLjE0YzAuMDMtMC4wNSwwLjA3LTAuMDksMC4xLTAuMTRjMC4zMS0wLjQ0LDAuNjItMC44OCwwLjk0LTEuMzIKCQljMC4zMi0wLjQ0LDAuNjItMC44NywwLjk0LTEuMzFjMC4zMS0wLjQ0LDAuNjMtMC44NywwLjk0LTEuM2MwLjMyLTAuNDMsMC42NC0wLjg3LDAuOTUtMS4zYzAuMjgtMC4zNywwLjU1LTAuNzQsMC44Mi0xLjExCgkJYzAuMjgtMC4zNywwLjU1LTAuNzMsMC44My0xLjFjMC4yOC0wLjM3LDAuNTYtMC43MywwLjgzLTEuMDljMC4yOC0wLjM2LDAuNTYtMC43MywwLjg0LTEuMDljMC4wNS0wLjA3LDAuMS0wLjEzLDAuMTUtMC4yCgkJYzAuMDUtMC4wNiwwLjEtMC4xMywwLjE1LTAuMmMwLjA1LTAuMDcsMC4xLTAuMTMsMC4xNS0wLjJjMC4wNS0wLjA3LDAuMS0wLjEzLDAuMTUtMC4xOWMwLjMzLTAuNDQsMC42Ny0wLjg3LDEuMDEtMS4zMQoJCWMwLjM0LTAuNDMsMC42Ny0wLjg2LDEuMDEtMS4yOWMwLjM0LTAuNDMsMC42OC0wLjg2LDEuMDItMS4yOWMwLjM0LTAuNDMsMC42OS0wLjg1LDEuMDMtMS4yOGMwLjM1LTAuNDMsMC42OS0wLjg2LDEuMDQtMS4yOAoJCWMwLjM1LTAuNDMsMC43LTAuODYsMS4wNC0xLjI4YzAuMzUtMC40MywwLjctMC44NSwxLjA1LTEuMjdjMC4zNS0wLjQyLDAuNy0wLjg0LDEuMDYtMS4yN2MyLjUtMi45OCw1LjA1LTUuODksNy42NC04Ljc1CgkJYzIuNi0yLjg3LDUuMjMtNS42OCw3LjkxLTguNDRjMi42OS0yLjc3LDUuNDItNS40OCw4LjE5LTguMTNjMi43OC0yLjY3LDUuNi01LjI4LDguNDYtNy44M2MwLjU1LTAuNDksMS4xLTAuOTgsMS42NS0xLjQ3CgkJYzAuNTUtMC40OSwxLjEtMC45NywxLjY2LTEuNDZjMC41Ni0wLjQ4LDEuMTEtMC45NywxLjY3LTEuNDRjMC41Ni0wLjQ4LDEuMTItMC45NiwxLjY4LTEuNDNjMC4xNy0wLjE0LDAuMzQtMC4yOSwwLjUtMC40MwoJCWMwLjE3LTAuMTQsMC4zNC0wLjI4LDAuNS0wLjQzYzAuMTctMC4xNCwwLjMzLTAuMjksMC41LTAuNDNjMC4xNi0wLjE0LDAuMzMtMC4yOCwwLjUtMC40MmMwLjQ0LTAuMzcsMC44OS0wLjc0LDEuMzMtMS4xCgkJYzAuNDQtMC4zNiwwLjg5LTAuNzMsMS4zMy0xLjA5YzAuNDUtMC4zNiwwLjg5LTAuNzIsMS4zNC0xLjA4YzAuNDUtMC4zNiwwLjktMC43MiwxLjM0LTEuMDhjMC42OS0wLjU1LDEuMzgtMS4xLDIuMDgtMS42NAoJCWMwLjY5LTAuNTUsMS4zOS0xLjA4LDIuMDktMS42MmMwLjctMC41NCwxLjQtMS4wNywyLjEtMS42YzAuNy0wLjUzLDEuNDEtMS4wNiwyLjExLTEuNThjMC44LTAuNiwxLjYtMS4xOSwyLjQxLTEuNzcKCQljMC44MS0wLjU5LDEuNjEtMS4xNywyLjQyLTEuNzVjMC44MS0wLjU4LDEuNjItMS4xNiwyLjQ0LTEuNzNjMC44Mi0wLjU3LDEuNjMtMS4xNCwyLjQ1LTEuN2MxLjU1LTEuMDYsMy4xLTIuMTEsNC42NS0zLjE0CgkJYzEuNTYtMS4wMywzLjEzLTIuMDUsNC43LTMuMDVjMC4yMy0wLjE1LDAuNDYtMC4yOCwwLjY5LTAuNDNsMC0wLjAxbC0xLjMzLTIuODNsLTAuMjYtMC4zM2MtMi4yMSwxLjA4LTQuNDIsMi4xNy02LjYxLDMuMjkKCQlDMTI1Ljk3LDI0Ljg0LDEyMy4yNSwyNi4yNiwxMjAuNTYsMjcuNzF6Ii8+CjwvZz4KPC9zdmc+Cg==';
		// Set the custom post type labels.
		$labels['name']                  = _x( 'Sitewide Sales', 'Post Type General Name', 'sitewide-sales' );
		$labels['singular_name']         = _x( 'Sitewide Sale', 'Post Type Singular Name', 'sitewide-sales' );
		$labels['all_items']             = __( 'All Sitewide Sales', 'sitewide-sales' );
		$labels['menu_name']             = __( 'Sitewide Sales', 'sitewide-sales' );
		$labels['name_admin_bar']        = __( 'Sitewide Sales', 'sitewide-sales' );
		$labels['all_items']             = __( 'All Sitewide Sales', 'sitewide-sales' );
		$labels['add_new_item']          = __( 'Add New Sitewide Sale', 'sitewide-sales' );
		$labels['add_new']               = __( 'Add New', 'sitewide-sales' );
		$labels['new_item']              = __( 'New Sitewide Sale', 'sitewide-sales' );
		$labels['edit_item']             = __( 'Edit Sitewide Sale', 'sitewide-sales' );
		$labels['update_item']           = __( 'Update Sitewide Sale', 'sitewide-sales' );
		$labels['view_item']             = __( 'View Sitewide Sale', 'sitewide-sales' );
		$labels['search_items']          = __( 'Search Sitewide Sales', 'sitewide-sales' );
		$labels['not_found']             = __( 'Not found', 'sitewide-sales' );
		$labels['not_found_in_trash']    = __( 'Not found in Trash', 'sitewide-sales' );
		$labels['insert_into_item']      = __( 'Insert into Sitewide Sale', 'sitewide-sales' );
		$labels['uploaded_to_this_item'] = __( 'Uploaded to this Sitewide Sale', 'sitewide-sales' );
		$labels['items_list']            = __( 'Sitewide Sales list', 'sitewide-sales' );
		$labels['items_list_navigation'] = __( 'Sitewide Sales list navigation', 'sitewide-sales' );
		$labels['filter_items_list']     = __( 'Filter Sitewide Sales list', 'sitewide-sales' );

		// Build the post type args.
		$args['labels']              = __( 'Sitewide Sales', 'sitewide-sales' );
		$args['labels']              = $labels;
		$args['description']         = __( 'Sitewide Sales', 'sitewide-sales' );
		$args['public']              = false;
		$args['publicly_queryable']  = false;
		$args['show_ui']             = true;
		$args['show_in_menu']        = true;
		$args['menu_position']       = 56;
		$args['menu_icon']			 = $menu_icon_svg;
		$args['show_in_nav_menus']   = true;
		$args['can_export']          = true;
		$args['has_archive']         = false;
		$args['rewrite']             = false;
		$args['exclude_from_search'] = true;
		$args['query_var']           = false;
		$args['capability_type']     = 'page';
		$args['show_in_rest']        = false;
		$args['rest_base']           = 'sitewide_sale';
		$args['supports']            = array( 'title', );
		register_post_type( 'sitewide_sale', $args );
	}

	/**
	 * [enqueue_scripts description]
	 *
	 * @return [type] [description]
	 */
	public static function enqueue_scripts() {
		wp_register_script( 'swsales_set_active_sitewide_sale', plugins_url( 'js/swsales-set-active-sitewide-sale.js', SWSALES_BASENAME ), array( 'jquery' ), '1.0.4' );
		wp_enqueue_script( 'swsales_set_active_sitewide_sale' );
	}

	/**
	 * set_sitewide_sale_columns Assigning labels to WP_List_Table columns will add a checkbox to the full list page's Screen Options.
	 *
	 * @param [type] $columns [description]
	 */
	public static function set_sitewide_sale_columns( $columns ) {
		unset( $columns['date'] );
		$columns['sale_date']    = __( 'Sale Date', 'sitewide-sales' );
		$columns['sale_type']    = __( 'Sale Type', 'sitewide-sales' );
		$columns['landing_page'] = __( 'Landing Page', 'sitewide-sales' );
		$columns['reports']      = __( 'Reports', 'sitewide-sales' );
		$columns['set_active']   = __( 'Select Active Sale', 'sitewide-sales' );

		return $columns;
	}

	/**
	 * [fill_sitewide_sale_columns description]
	 *
	 * @param  [type] $column  [description]
	 * @param  [type] $post_id [description]
	 * @return [type]          [description]
	 */
	public static function fill_sitewide_sale_columns( $column, $post_id ) {
		$sitewide_sale = SWSales_Sitewide_Sale::get_sitewide_sale( $post_id );

		switch ( $column ) {
			case 'sale_date':
				echo esc_html( $sitewide_sale->get_start_date() . ' - ' . $sitewide_sale->get_end_date() );
				break;
			case 'sale_type':
				$sale_type = get_post_meta( $post_id, 'swsales_sale_type', true );
				if ( '0' !== $sale_type ) {
					$sale_types = apply_filters( 'swsales_sale_types', array() );
					echo esc_html( $sale_types[ $sale_type ] );
				}
				break;
			case 'landing_page':
				$landing_page = $sitewide_sale->get_landing_page_post_id();
				if ( ! empty( $landing_page ) ) {
					$title = get_the_title( $landing_page );
					if ( ! empty( $title ) ) {
						echo '<a href="' . esc_url( get_permalink( $landing_page ) ) . '">' . esc_html( $title ) . '</a>';
					}
				} else {
					echo '-';
				}
				break;
			case 'reports':
					echo '<a class="button button-primary" href="' . admin_url( 'edit.php?post_type=sitewide_sale&page=sitewide_sales_reports&sitewide_sale=' . $post_id ) . '">' . esc_html__( 'View Reports', 'pmpro-sitewide-sales' ) . '</a>';
				break;
			case 'set_active':
				$options = SWSales_Settings::get_options();
				if ( array_key_exists( 'active_sitewide_sale_id', $options ) && $post_id == $options['active_sitewide_sale_id'] ) {
					echo '<button class="button button-primary swsales_column_set_active" id="swsales_column_set_active_' . $post_id . '">' . __( 'Remove Active', 'sitewide-sales' ) . '</button>';
				} else {
					echo '<button class="button button-secondary swsales_column_set_active" id="swsales_column_set_active_' . $post_id . '">' . __( 'Set Active', 'sitewide-sales' ) . '</button>';
				}
				break;
		}
	}

	/**
	 * [set_active_sitewide_sale description]
	 */
	public static function set_active_sitewide_sale() {
		$sitewide_sale_id = $_POST['sitewide_sale_id'];
		$options          = SWSales_Settings::get_options();

		if ( array_key_exists( 'active_sitewide_sale_id', $options ) && $sitewide_sale_id == $options['active_sitewide_sale_id'] ) {
			$options['active_sitewide_sale_id'] = false;
		} else {
			$options['active_sitewide_sale_id'] = $sitewide_sale_id;
		}

		SWSales_Settings::save_options( $options );
	}

	/**
	 * [remove_sitewide_sale_row_actions description]
	 */
	public static function remove_sitewide_sale_row_actions( $actions, $post ) {
		// Removes the "Quick Edit" action.
		if ( $post->post_type === 'sitewide_sale' ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		
		return $actions;
	}

	/**
	 * Make sure status is always publish.
	 * We must allow trash and auto-draft as well.
	 */
	public static function force_publish_status( $data, $postarr ) {
		if ( $data['post_type'] === 'sitewide_sale'
		   && $data['post_status'] !== 'trash'
		   && $data['post_status'] !== 'auto-draft' ) {
			$data['post_status'] = 'publish';
		}

		return $data;
	}

}
