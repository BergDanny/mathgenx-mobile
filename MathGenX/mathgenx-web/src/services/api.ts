import ky from "ky";

export const api = ky.create({
  prefixUrl: import.meta.env.VITE_API_BASE,
  timeout: 180000,
  hooks: {
    beforeRequest: [
        (request) => {
            const token = localStorage.getItem("token");
            if (token) {
                request.headers.set("Authorization", `Bearer ${token}`);
            }
        }
    ]
  }
});