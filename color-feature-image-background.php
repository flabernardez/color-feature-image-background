<?php
/**
 * Plugin Name: Color Feature Image Background
 * Plugin URI: https://flabernardez.com
 * Description: A plugin that captures main colors of the feature image from a post and pick 5 of them to be the background color of that post.
 * Version: 1.0
 * Author: Flavia Bernárdez Rodríguez
 * Text Domain: cfi-background
 *
 * @package cfi-background
 */

require_once plugin_dir_path( __FILE__ ) . 'image-sample.php';
use Chirp\ImageSampler;

/**
 * Registro del metabox de selección del color de la imagen destacada
 */
function cfi_register_meta_box() {
	add_meta_box(
		'cfi_color_selector', // ID de la metabox
		'Fondo', // Título de la metabox
		'cfi_display_color_selector', // Función de callback que muestra el contenido de la metabox
		'post', // Tipo de pantalla donde se mostrará esta metabox (en este caso, posts)
		'side', // Contexto donde se mostrará la metabox (side, advanced, normal)
		'high' // Prioridad de la metabox
	);
}
add_action('add_meta_boxes', 'cfi_register_meta_box');

function cfi_display_color_selector($post) {
	// Obtener la URL de la imagen destacada del post
	$featured_image_url = get_the_post_thumbnail_url($post->ID, 'full');

	if ($featured_image_url) {
		// Asegúrate de que la clase ImageSampler puede manejar la URL directamente
		// Aquí, adaptamos el código para trabajar con la clase tal como fue proporcionada
		// Nota: Esto requiere que tu servidor permita abrir URLs con funciones de imagen de PHP
		$sampler = new \Chirp\ImageSampler($featured_image_url);
		$sampler->set_percent(5); // Ajusta estos valores según sea necesario
		$sampler->set_steps(10); // Ajusta estos valores según sea necesario
		$sampler->init(); // Inicializa las dimensiones de muestreo

		// Obtiene la matriz de colores muestreados
		$colors_matrix = $sampler->sample();

		// Procesamiento adicional para extraer colores individuales de la matriz
		// Este es un ejemplo simplificado. Deberás adaptar esto según tus necesidades
		$colors = [];
		foreach ($colors_matrix as $row) {
			foreach ($row as $color) {
				$colors[] = sprintf("#%02x%02x%02x", $color[0], $color[1], $color[2]);
			}
		}

		// Elimina colores duplicados y selecciona los primeros 5 únicos
		$unique_colors = array_unique($colors);
		$top_colors = array_slice($unique_colors, 0, 5);

		// Verifica si ya se ha seleccionado un color para este post
		$selected_color = get_post_meta($post->ID, '_cfi_selected_color', true);

		echo '<p>Colores a partir de la imagen destacada de esta publicación, que se aplicará como color de fondo.</p>';
		echo '<div id="cfi_color_selector">';

		foreach ($top_colors as $color) {
			// Marca el color seleccionado previamente
			$checked = checked($selected_color, $color, false);
			echo "<label style='background-color: {$color}; margin-right: 5px; padding: 10px; display: inline-block; border: solid 1px #ccc;'>";
			echo "<input type='radio' name='cfi_selected_color' value='{$color}' {$checked}> {$color}";
			echo '</label>';
		}

		echo '</div>';
	} else {
		echo '<p>Por favor, establece una imagen destacada para este post para extraer colores.</p>';
	}
}


function cfi_save_selected_color($post_id) {
	if (array_key_exists('cfi_selected_color', $_POST)) {
		update_post_meta($post_id, '_cfi_selected_color', $_POST['cfi_selected_color']);
	}
}
add_action('save_post', 'cfi_save_selected_color');

function cfi_apply_background_color() {
	if (is_single()) { // Asegúrate de que esto solo se ejecute en páginas de posts individuales
		global $post;
		$selected_color = get_post_meta($post->ID, '_cfi_selected_color', true);
		if (!empty($selected_color)) {
			echo "<style>body { background-color: {$selected_color}; }</style>";
		}
	}
}
add_action('wp_head', 'cfi_apply_background_color');
