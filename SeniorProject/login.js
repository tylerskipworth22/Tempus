// login.js
import { auth, signInWithEmailAndPassword } from "./firebase.js";

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("loginForm");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value.trim();

        if (!email || !password) {
            alert("Please enter both email and password.");
            return;
        }

        try {
            const userCredential = await signInWithEmailAndPassword(auth, email, password);
            const user = userCredential.user;
            const idToken = await user.getIdToken();

            const response = await fetch("login.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ idToken }),
                credentials: "include" 
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch {
                alert("Server returned invalid response.");
                console.error("Invalid response:", text);
                return;
            }

            if (data.success) {
                window.location.href = "index.html";
            } else {
                alert("Login failed: " + (data.message || "Unknown error"));
            }

        } catch (err) {
            alert("Firebase login error: " + err.message);
            console.error(err);
        }
    });
});
