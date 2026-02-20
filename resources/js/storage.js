/**
 * Storage abstraction for file uploads.
 * Uses local storage when MIX_MEDIA_STORAGE=local, otherwise uses Laravel Vapor (S3).
 */
import axios from "./axios";
import vapor from "laravel-vapor";

// Runtime (from server) takes precedence over build-time (MIX_MEDIA_STORAGE)
const useLocalStorage =
	(typeof window !== "undefined" && window.MEDIA_STORAGE === "local") ||
	process.env.MIX_MEDIA_STORAGE === "local";

async function storeLocal(file, options = {}) {
	const formData = new FormData();
	formData.append("file", file);

	const response = await axios.post("/local-storage-upload", formData, {
		headers: {
			"Content-Type": "multipart/form-data",
		},
	});

	return {
		uuid: response.data.uuid,
		key: response.data.key,
	};
}

async function store(file, options = {}) {
	if (useLocalStorage) {
		return storeLocal(file, options);
	}
	return vapor.store(file, {
		visibility: options.visibility || "public-read",
		contentType: options.contentType || file.type,
	});
}

export default { store, useLocalStorage };
