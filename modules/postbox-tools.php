<?php
/*
 * Description: Helper for generating postbox texts, buttons and other actions.
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
    die('Access denied!');
}

if ( ! class_exists('Postbox_Tools') ) {

    class Postbox_Tools {

        /**
         * Display basic Seravo Plugin widget text
         * @param string $content The content to display
         * @return string Return the HTML code
         */
        public static function text( $content ) {
            return '<div class="postbox-text">' . $content . '</div>';
        }

        /**
         * Display HTML paragraph with given content
         * @param string $content The content to display
         * @param string $class Paragraph class to apply
         * @return string Return the HTML code
         */
        public static function paragraph( $content, $class = 'paragraph-text' ) {
            return '<p class="' . $class . '">' . $content . '</p>';
        }

        /**
         * Display section title on widget
         * @param string $content The given title to display
         * @return string Return the HTML code
         */
        public static function section_title( $content ) {
            return '<h3>' . $content . '</h3>';
        }

        /**
         * Display Seravo Plugin tooltip
         * @param string $tooltiptext The given content to display on tooltip
         * @return string Return the HTML code
         */
        public static function tooltip( $tooltiptext ) {
            return '<span class="tooltip dashicons dashicons-info"> <span class="tooltiptext"> ' .
                $tooltiptext . '</span></span>';
        }

        /**
         * Display spinner image. In default it's hidden
         * @param string $id Id for this spinner
         * @return string Return the HTML code
         */
        public static function spinner( $id ) {
            return '<div id="' . $id . '"><img src="/wp-admin/images/spinner.gif" hidden></div>';
        }

        /**
         * Display basic clickable / interactive button to run for example AJAX side commands
         * $content Text to display in the button
         * @param string $id Button id
         * @param string $class Specified button class to use
         * @return string Return the HTML code
         */
        public static function action_button( $content, $id, $class = 'button-primary' ) {
            return '<button id="' . $id . '" class="' . $class . '">' . $content . '</button>';
        }

        /**
         * Display 'Toggle more' stylish result wrapper
         * @param string $id ID for accessing on jQuery side and to name specific components uniquely.
         * @param string $title Titletext for the wrapper box
         * @return string Return the HTML code
         */
        public static function result_wrapper( $id, $title, $button_text = '' ) {
            $wrapper = '';
            if ( $button_text !== '' ) {
                $wrapper .= self::action_button($button_text, 'button_' . $id);
            }

            $toggle_icons = '<div class="seravo_show_more_wrapper" hidden>
                        <a href="" class="seravo_show_more">' . __('Toggle Details', 'seravo') .
                '<div class="dashicons dashicons-arrow-down-alt2" id="seravo_arrow_"' . $id . '_show_more>
                        </div> </a> </div>';

            return $wrapper . ('<div class="seravo-result-wrapper">
                        <div class="seravo_result_wrapper_title">' . $title . '</div>
                        <div class="seravo-result"> <div id="' . $id . '"></div> </div>' .
                $toggle_icons . '</div>');
        }
    }
}
