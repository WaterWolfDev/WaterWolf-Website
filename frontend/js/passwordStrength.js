export default function passwordStrength(
    passwordFieldId = 'passwordField',
    passwordStrengthBarId = 'passwordStrengthBar',
    passwordStrengthTextId = 'passwordStrengthText'
) {
    document.getElementById(passwordFieldId).addEventListener("input", function () {
        let val = this.value;
        let strength = getStrength(val);
        let progressBar = document.getElementById(passwordStrengthBarId);
        let strengthText = document.getElementById(passwordStrengthTextId);

        progressBar.style.width = strength.percent + "%";

        switch (strength.level) {
            case 0:
                progressBar.className = "progress-bar bg-danger";
                strengthText.innerText = "Weak";
                break;
            case 1:
                progressBar.className = "progress-bar bg-warning";
                strengthText.innerText = "Moderate";
                break;
            case 2:
                progressBar.className = "progress-bar bg-success";
                strengthText.innerText = "Strong";
                break;
        }
    });

    function getStrength(password) {
        let strength = {
            level: 0,
            percent: 0
        };

        if (password.length >= 8) strength.percent += 33;
        if (/[A-Z]/.test(password)) strength.percent += 33;
        if (/[0-9]/.test(password)) strength.percent += 34;

        if (strength.percent <= 33) strength.level = 0;
        else if (strength.percent <= 66) strength.level = 1;
        else strength.level = 2;

        return strength;
    }
}
