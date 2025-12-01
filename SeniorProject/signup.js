import { auth } from "./firebase.js";
import { createUserWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-auth.js";

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("signupForm");
    if (!form) return;

    form.addEventListener("submit", async (e) => {
        e.preventDefault(); // Stop normal form submission

        const email = document.getElementById("email").value.trim();
        const username = document.getElementById("user").value.trim();
        const password = document.getElementById("password").value.trim();
        const passwordRepeat = document.getElementById("passwordRepeat").value.trim();

        if (password !== passwordRepeat) {
            alert("Passwords do not match!");
            return;
        }

        try {
            // Create Firebase Auth user
            const userCredential = await createUserWithEmailAndPassword(auth, email, password);
            const user = userCredential.user;
            const idToken = await user.getIdToken();

            // Send token + username/email to PHP to insert into MySQL
            const response = await fetch("signup.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ idToken, email, username })
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                alert("Invalid response from server.");
                console.error(text);
                return;
            }

            if (data.success) {
                alert("Account created! You can now log in.");
                window.location.href = "login.html";
            } else {
                alert("Signup failed: " + (data.message || "Unknown error"));
            }

        } catch (err) {
            alert("Firebase signup error: " + err.message);
            console.error(err);
        }
    });
});
