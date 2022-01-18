<?php
/**
 * La configuration de base de votre installation WordPress.
 *
 * Ce fichier est utilisé par le script de création de wp-config.php pendant
 * le processus d’installation. Vous n’avez pas à utiliser le site web, vous
 * pouvez simplement renommer ce fichier en « wp-config.php » et remplir les
 * valeurs.
 *
 * Ce fichier contient les réglages de configuration suivants :
 *
 * Réglages MySQL
 * Préfixe de table
 * Clés secrètes
 * Langue utilisée
 * ABSPATH
 *
 * @link https://fr.wordpress.org/support/article/editing-wp-config-php/.
 *
 * @package WordPress
 */

// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define( 'DB_NAME', 'simplon' );

/** Utilisateur de la base de données MySQL. */
define( 'DB_USER', 'root' );

/** Mot de passe de la base de données MySQL. */
define( 'DB_PASSWORD', '' );

/** Adresse de l’hébergement MySQL. */
define( 'DB_HOST', 'localhost' );

/** Jeu de caractères à utiliser par la base de données lors de la création des tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/**
 * Type de collation de la base de données.
 * N’y touchez que si vous savez ce que vous faites.
 */
define( 'DB_COLLATE', '' );

/**#@+
 * Clés uniques d’authentification et salage.
 *
 * Remplacez les valeurs par défaut par des phrases uniques !
 * Vous pouvez générer des phrases aléatoires en utilisant
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ le service de clés secrètes de WordPress.org}.
 * Vous pouvez modifier ces phrases à n’importe quel moment, afin d’invalider tous les cookies existants.
 * Cela forcera également tous les utilisateurs à se reconnecter.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '#>V-[^/epI*0#f,rT{ZIMm4aZ1b./H4uJ}1=CUl8?+HJEBO&c&247}n)-.1AjL[(' );
define( 'SECURE_AUTH_KEY',  'QKzp}2{d/`cx&cxMGTe,Z&r6XE->YsRM$!dV$mDfV$:2se_ic_N}hE#G{ 7hLD@l' );
define( 'LOGGED_IN_KEY',    'W9b=tLP@Sut?zw@RGQ/pp+~.x.0dZp~YAv]t(h,R95S)7h8qP9l4<.S2+/c]s(d@' );
define( 'NONCE_KEY',        'bqT Ktw~IzL)d_S!y-vip4^Q`-qr XCq^&s[&lNP$U&{W#YMQPwC`&cR${;:LCPL' );
define( 'AUTH_SALT',        'u=6e{3+G3 4b48Na@_N;0mYoW/v`PVx;0^gbW+vy2{#t47$7,n.dkSO![n&ZCs]R' );
define( 'SECURE_AUTH_SALT', '&/DgtlgPV#dYqO34kw#PRlN9e3Y7Uodb9%#ZaeJ7J:S)hw#f2#!A;rrgg)%,%eYp' );
define( 'LOGGED_IN_SALT',   '*zr!>OU]p79T w^UX+]xpi%5e.9wAA]|tq%#1-gr?%|Ffzz8@mcS<%Q_fXsJyMR5' );
define( 'NONCE_SALT',       '4!kM0)--nGtVAz3#yH-rAt?/p`Z>cL;${I%5ofbs%]$/gmdX]/+)Iv)MaQxy9G^*' );
/**#@-*/

/**
 * Préfixe de base de données pour les tables de WordPress.
 *
 * Vous pouvez installer plusieurs WordPress sur une seule base de données
 * si vous leur donnez chacune un préfixe unique.
 * N’utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés !
 */
$table_prefix = 'wp_';

/**
 * Pour les développeurs : le mode déboguage de WordPress.
 *
 * En passant la valeur suivante à "true", vous activez l’affichage des
 * notifications d’erreurs pendant vos essais.
 * Il est fortement recommandé que les développeurs d’extensions et
 * de thèmes se servent de WP_DEBUG dans leur environnement de
 * développement.
 *
 * Pour plus d’information sur les autres constantes qui peuvent être utilisées
 * pour le déboguage, rendez-vous sur le Codex.
 *
 * @link https://fr.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* C’est tout, ne touchez pas à ce qui suit ! Bonne publication. */

/** Chemin absolu vers le dossier de WordPress. */
if ( ! defined( 'ABSPATH' ) )
  define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Réglage des variables de WordPress et de ses fichiers inclus. */
require_once( ABSPATH . 'wp-settings.php' );
