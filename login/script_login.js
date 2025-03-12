const container = document.getElementById("container");
const registerBtn = document.getElementById("register");
const loginBtn = document.getElementById("login");

registerBtn.addEventListener("click", () => {
  container.classList.add("active");
});

loginBtn.addEventListener("click", () => {
  container.classList.remove("active");
});

// Mobile toggle functionality
document.addEventListener('DOMContentLoaded', function() {
  // Get all mobile switch buttons
  const toSigninButtons = document.querySelectorAll('.to-signin');
  const toSignupButtons = document.querySelectorAll('.to-signup');
  
  // Add click events for mobile buttons
  toSigninButtons.forEach(button => {
    button.addEventListener('click', function() {
      container.classList.remove('active');
      updateActiveButtons();
    });
  });
  
  toSignupButtons.forEach(button => {
    button.addEventListener('click', function() {
      container.classList.add('active');
      updateActiveButtons();
    });
  });
  
  // Update active state of buttons based on container state
  function updateActiveButtons() {
    const isActive = container.classList.contains('active');
    
    toSigninButtons.forEach(button => {
      button.classList.toggle('active', !isActive);
    });
    
    toSignupButtons.forEach(button => {
      button.classList.toggle('active', isActive);
    });
  }
});
