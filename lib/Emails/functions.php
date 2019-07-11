<?php

if (!function_exists('pf_formatted_list')) {
	/**
	 * Formats an array of items into a comma-separated list, with ' and ' to separate the last item
	 * @param array $list
	 * @return string
	 */
	function pf_formatted_list($list) {
		if (is_array($list)) {
			// Join all items, except the last, with a comma delimiter and make the string an array
			$main_list = array(join(', ', array_slice($list, 0, -1)));
			// Merge the new array with the last item in the original list
			$main_list = array_merge($main_list, array_slice($list, -1));
			// Reduce the array in case the original list has 1 or 2 items, then join it with ' and ' if applicable
			return join(' and ', array_filter($main_list, 'strlen'));
		}
		return '';
	}
}