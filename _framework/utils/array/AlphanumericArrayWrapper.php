<?php

	/**
	 *
	 * Wraps the array to make alphanumeric characters (ignoring case) the only thing considered when using a key to access a value.
	 * For example, the keys 'Hello_world!' and 'he(l)lo WORLD' would reference the same value.
	 *
	 * @author Thomas
	 */
	class AlphanumericArrayWrapper extends ArrayWrapper {

		public function makeQuickName($name) {
			return preg_replace('/[^a-z0-9]/', '', strtolower($name));
		}

		/**
		 * Returns an AlphanumericArrayWrapper for the given data if possible.
		 * @param mixed $data The data to be wrapped.
		 * @return AlphanumericArrayWrapper The wrapped data.
		 */
		public static function wrap($data) {
			if (is_array($data))
				return new AlphanumericArrayWrapper($data);
			if ($data instanceof AlphanumericArrayWrapper)
				return $data;
			if ($data instanceof ArrayWrapper)
				return new AlphanumericArrayWrapper($data->getWrappedArray());
			user_error('Object of type ' . gettype($data) . ' is not a valid target for wrapping. Must be an array or ArrayWrapper.');
			return null;
		}

	}

?>