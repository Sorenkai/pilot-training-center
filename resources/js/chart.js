/**
 * Imports
*/

import Chart from 'chart.js/auto';
import * as helpers from 'chart.js/helpers';
import 'chartjs-adapter-moment';
import ChartDataLabels from 'chartjs-plugin-datalabels';


/**
 * Insert global variables
*/

Chart.helpers = helpers;
window.Chart = Chart;

// Register plugins
