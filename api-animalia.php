<?php
/**
 * Plugin Name: API Animalia Grand Public
 * Plugin URI: http://animalia.univ-tours.fr
 * Description: API publique du projet Animalia
 * Version: 0.4
 * Author: Projet Animalia
 * Author URI: http://vag.ovh/
 */

require 'JwtHandler.php';
 $jwt = new JwtHandler();
//  $token = $_GET['token'] && !empty($_GET['token']) ? $_GET['token'] : null;

function api_animals(){
    $args = [
        'numberposts' => 99999,
        'post_type' => 'animal',
    ];

    $animals = get_posts($args);

    $data = [];
    $i = 0;

    foreach($animals as $animal){
        $data[$i]['id'] = $animal->ID;
        $data[$i]['nom'] = $animal->post_name; //c'est un peu fou mais du coup le slug est renommé post_name dans la classe
        //on ternaire les groupes metazoaires et moyen de locomotions car sinon erreur s'il manque l'un ou l'autre
        $data[$i]['groupe_metazoaires'] = get_the_terms( $animal->ID, 'metazoaires') ? wp_list_pluck(get_the_terms( $animal->ID, 'metazoaires'), 'name') : "";
        $data[$i]['moyen_locomotion'] = get_the_terms( $animal->ID, 'locomotion') ? wp_list_pluck(get_the_terms( $animal->ID, 'locomotion'), 'name') : "";
        $data[$i]["link"] = get_the_permalink($animal->ID);
        //wp_list_pluck : permet d'extraire la valeur d'une clef (en str'') d'un array #fonction du bonheur
        // $data[$i]['lien'] = $animal->post_link;
        
        if(isset($_GET['token']) && $_GET['token']){
            $jwt = new JwtHandler();
            $token =  $jwt->_jwt_decode_data(trim($_GET['token']));
            if($token && $token->exp > time()){
                $data[$i]['description'] = wp_strip_all_tags($animal->post_content); // ok donc CTP ou non on utilise post_
                $data[$i]['media']['thumbnail'] = get_the_post_thumbnail_url($animal->ID, 'thumbnail', 'small' );
                $data[$i]['media']['crane']['dorsal'] = get_field( 'dorsal2d', $animal->ID ) ;
                $data[$i]['media']['crane']['ventral'] = get_field( 'ventral2d', $animal->ID ) ;
                $data[$i]['media']['crane']['lateral'] = get_field( 'lateral2d', $animal->ID ) ;
                $data[$i]['media']['modele']['crane'] = get_field( 'modele_3d', $animal->ID ) ;
                $data[$i]['media']['json']['ventral'] = get_field( 'metadata_ventral', $animal->ID ) ;
                $data[$i]['media']['json']['dorsal'] = get_field( 'metadata_dorsal', $animal->ID ) ;
                $data[$i]['media']['json']['lateral'] = get_field( 'metadata_lateral', $animal->ID ) ;
            }
        }



        $i++;
    }

    return $data;
}
/**
 * 
 */

 function api_metazoaires(){

    $args = array(
        'name' => 'metazoaires',
    );

    $metazoaires = get_terms('metazoaires', array('hide_empty' => false));
    // $categoryHierarchy = array();
    // sort_terms_hierarchically($categories, $categoryHierarchy);

    $data = [];
    $i=0;

    foreach($metazoaires as $metazoaire){
        $data[$i]['id'] = $metazoaire->term_id;
        $data[$i]['parentId'] = $metazoaire->parent;
        $data[$i]['name'] = $metazoaire->name;
        $data[$i]['description'] = wp_strip_all_tags($metazoaire->description);
        $data[$i]['count'] = $metazoaire->count;
        $data[$i]["post_type"] = get_post_type($metazoaire->term_id);
        

        if(isset($_GET['token']) && $_GET['token']){
            $data[$i]['offspring'] = array();
            $jwt = new JwtHandler();
            $token =  $jwt->_jwt_decode_data(trim($_GET['token']));
            if($token && $token->exp > time()){
                $query = new WP_Query( array(
                    'post_type' => 'animal',  // Or your custom post type's slug
                    'posts_per_page' => -1, // Do not paginate posts
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'metazoaires',
                            'field' => 'term_id',
                            'terms' => $metazoaire->term_id
                        )
                    )
                ) );
        
                if ( $query->have_posts() ):
                    while ( $query->have_posts() ): $query->the_post();
                        //$data[$i]["animaux"] .= get_the_title(). ", ";
                        array_push($data[$i]["offspring"], array(
                                                                    "id" => get_the_ID(),
                                                                    "name" => get_the_title(),
                                                                    "description" => wp_strip_all_tags(get_the_content()),
                                                                ),
                                                                    
                                    );
                    endwhile;
                endif;
            }
        }

        

        $i++;        
    }

    return $data;
 }

/**
 * 
 */
// wp_strip_all_tags -> Retire tout les tags html d'un contenu. Y compris les styles, scripts et les balises natives de wordpress

//retour d'un animal avec son nom en slug/param
function api_animal( $slug ) {
    //retourne les infos d'un seul élément (par son slug, donc string ex:canard)
	$args = [
		'name' => $slug['slug'],
		'post_type' => 'animal',
	];

	$post = get_posts($args);

	$data['id'] = $post[0]->ID;
    $data['nom'] = $post[0]->post_name;
    //c'est un peu fou mais du coup le slug est renommé post_name dans la classe
    
    //$data[$i]['media']['thumbnail'] = get_the_post_thumbnail_url($post[0]->ID, 'thumbnail' ); //fonction native. Non spécifique à WP
    
    if(isset($_GET['token']) && $_GET['token']){
        $jwt = new JwtHandler();
        $token =  $jwt->_jwt_decode_data(trim($_GET['token']));
        if($token && $token->exp > time()){
            $data['article'] = wp_strip_all_tags($post[0]->post_content); // ok donc CTP ou non on utilise post_
            $data['media']['thumbnail'] = get_the_post_thumbnail_url($post[0]->ID, 'thumbnail' );
            $data['media']['crane']['dorsal'] = get_field( 'dorsal2d', $post[0]->ID ) ;
            $data['media']['crane']['ventral'] = get_field( 'ventral2d', $post[0]->ID ) ;
            $data['media']['crane']['lateral'] = get_field( 'lateral2d', $post[0]->ID ) ;
            $data['media']['modele3d']['crane'] = get_field( 'modele_3d', $post[0]->ID ) ;
        }
    }

	return $data;
}


//retour d'un animal avec son nom en slug/param
function api_animal_id( $slug ) {
    //retourne les infos d'un seul élément (par son id, donc string ex:245)
	$args = [
		'id' => $slug['id'],
		'post_type' => 'animal',
	];

	$post = get_post($slug['id'], array('post_type' => 'post_type_name'));

	$data['id'] = get_post($slug['id'])->ID;
    $data['nom'] = get_post($slug['id'])->post_name;
    //c'est un peu fou mais du coup le slug est renommé post_name dans la classe
    
    //$data[$i]['media']['thumbnail'] = get_the_post_thumbnail_url(get_post($slug['id'])->ID, 'thumbnail' ); //fonction native. Non spécifique à WP
    
    if(isset($_GET['token']) && $_GET['token']){
        $jwt = new JwtHandler();
        $token =  $jwt->_jwt_decode_data(trim($_GET['token']));
        if($token && $token->exp > time()){
            $data['article'] = wp_strip_all_tags(get_post($slug['id'])->post_content); // ok donc CTP ou non on utilise post_
            $data['media']['thumbnail'] = get_the_post_thumbnail_url(get_post($slug['id'])->ID, 'thumbnail' );
            $data['media']['crâne']['dorsal'] = get_field( 'dorsal2d', get_post($slug['id'])->ID ) ;
            $data['media']['crâne']['ventral'] = get_field( 'ventral2d', get_post($slug['id'])->ID ) ;
            $data['media']['crâne']['lateral'] = get_field( 'lateral2d', get_post($slug['id'])->ID ) ;
            $data['media']['modele']['crane'] = get_field( 'modele_3d', get_post($slug['id'])->ID ) ;
        }
    }

    if(get_post_type($slug['id']) === "animal"){
        return $data;
    }else{
        $erreur['erreur'] = "Cet ID ne correspond à aucun animal.";
        return $erreur;
    }
	
}


function api_metazoaire(){
    $args = array(
        'name' => 'metazoaires',
    );

    $metazoaires = get_terms('metazoaires', array('hide_empty' => false));
    // $categoryHierarchy = array();
    // sort_terms_hierarchically($categories, $categoryHierarchy);

    $data = [];
    $i=0;

    foreach($metazoaires as $metazoaire){
        $data[$i]['id'] = $metazoaire->term_id;
        $data[$i]['parentId'] = $metazoaire->parent;
        $data[$i]['nom'] = $metazoaire->name;
        
        if(isset($_GET['token']) && $_GET['token']){
            $jwt = new JwtHandler();
            $token =  $jwt->_jwt_decode_data(trim($_GET['token']));
            if($token && $token->exp > time()){
               $data[$i]['description'] = $metazoaire->description; 
            }
        }
        
        $i++;        
    }

    return $data;
 }



 function api_metazoaire_id($slug){
    $args = array(
        'id' => $slug['id'],
        'name' => 'metazoaires',
    );

    // if(get_post_type($slug['id']) === "revision"){
        $metazoaire = get_term($slug['id'], 'metazoaires');
        $data = [];
        //$i=0;
    
        //foreach($metazoaires as $metazoaire){
            $data['id'] = $metazoaire->term_id;
            $data['parentId'] = $metazoaire->parent;
            $data['nom'] = $metazoaire->name;
            $data['description'] = $metazoaire->description;
            $data["post_type"] = get_post_type($slug['id']);

            if(isset($_GET['token']) && $_GET['token']){
                $data['offspring'] = array();
                $jwt = new JwtHandler();
                $token =  $jwt->_jwt_decode_data(trim($_GET['token']));
                if($token && $token->exp > time()){
                    $query = new WP_Query( array(
                        'post_type' => 'animal',  // Or your custom post type's slug
                        'posts_per_page' => -1, // Do not paginate posts
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'metazoaires',
                                'field' => 'term_id',
                                'terms' => $metazoaire->term_id
                            )
                        )
                    ) );
            
                    if ( $query->have_posts() ):
                        while ( $query->have_posts() ): $query->the_post();
                            //$data[$i]["animaux"] .= get_the_title(). ", ";
                            array_push($data["offspring"], array(
                                                                        "id" => get_the_ID(),
                                                                        "name" => get_the_title(),
                                                                        "description" => wp_strip_all_tags(get_the_content()),
                                                                    ),
                                                                        
                                        );
                        endwhile;
                    endif;
                }
            }
    
        return $data;
    // }else{
    //     $erreur['erreur'] = "Cet ID ne correspond à aucun métazoaire.";
    //     return $erreur;
    // }
    //return $data;
 }

 function api_arbre(){

    $args = array(
        'name' => 'metazoaires',
    );

    $metazoaires = get_terms('metazoaires', array('hide_empty' => false));
    // $categoryHierarchy = array();
    // sort_terms_hierarchically($categories, $categoryHierarchy);

    $data = [];
    $i=0;

    foreach($metazoaires as $metazoaire){
        $data[$i]['nodeId'] = strval($metazoaire->term_id);
        $data[$i]['parentNodeId'] = $metazoaire->parent == 0 ? null : strval($metazoaire->parent);
        //Important pour l'arbre chart : Le parent suprême a besoin d'avoir NULL comme valeur
        $data[$i]['name'] = $metazoaire->name;
        $data[$i]['image'] = get_template_directory_uri() . "/img/bubble.png";

        $i++;        
    }

    return $data;
 }


 add_action('rest_api_init', function(){
    register_rest_route('api/v1', 'animal', [
        'methods' => 'GET',
        'callback' => 'api_animals',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route( 'api/v1', 'animal/(?P<slug>[a-zA-Z0-9-]+)', array(
		'methods' => 'GET',
		'callback' => 'api_animal',
        'permission_callback' => '__return_true',
    ) );

    register_rest_route( 'api/v1', 'animal/id/(?P<id>\d+)', array(
		'methods' => 'GET',
		'callback' => 'api_animal_id',
        'permission_callback' => '__return_true',
    ) );

    register_rest_route('api/v1', 'metazoaires', [
        'methods' => 'GET',
        'callback' => 'api_metazoaires',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route( 'api/v1', 'metazoaires/(?P<slug>[a-zA-Z0-9-]+)', array(
		'methods' => 'GET',
		'callback' => 'api_metazoaire',
        'permission_callback' => '__return_true',
    ) );

    register_rest_route( 'api/v1', 'metazoaires/id/(?P<id>\d+)', array(
		'methods' => 'GET',
		'callback' => 'api_metazoaire_id',
        'permission_callback' => '__return_true',
    ) );

    register_rest_route('api/v1', 'arbre', [
        'methods' => 'GET',
        'callback' => 'api_arbre',
        'permission_callback' => '__return_true',
    ]);

 });