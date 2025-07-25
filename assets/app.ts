/**
 * Welcome to your app's main TypeScript file!
 *
 * We recommend including the built version of this TypeScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Import Bootstrap CSS and JS
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Import SCSS styles - will be compiled to CSS
import './styles/app.scss';

// Import Stimulus controllers
import { app } from './bootstrap.js';

// Expose Stimulus app globally for debugging
(window as any).Stimulus = app;

// Import React components
import { registerReactControllerComponents } from '@symfony/ux-react';
registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));



