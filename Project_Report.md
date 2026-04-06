# SplitEase: Project Report

## 1. General Overview & Project Scope
SplitEase is a web-based expense-splitting application similar to Splitwise. Its primary objective is to allow user groups to track collective spending, divide costs fairly among group members, and compute precise balances regarding who owes whom. 

The scope of this project is deliberately constrained to focus on a complete, robust web experience without unnecessary complexity. Features like mobile app integration, payment gateway processing, or receipt scanning are explicitly excluded to maintain a strictly manageable 4-week development lifecycle.

## 2. Business Value
SplitEase solves a common and uncomfortable problem: the awkwardness and friction of managing shared finances among peers. Roommates, travel companions, and event organizers often resort to complex spreadsheets or messy group chats. SplitEase brings business value by offering a clean, automated, and free tool that prevents financial misunderstandings and securely preserves the history of shared transactions.

## 3. Technology Stack & Software Engineering Model
### Model: Agile (Scrum-lite)
The team has opted for an Agile framework, specifically a simplified Scrum model consisting of short 1-week to 2-week iterations. This allows for rapid prototyping, continuous feedback, and faster defect discovery. Due to the tight 4-week deadline, the incremental approach ensures that at least a functional minimum viable product (MVP) is ready early in the cycle.

### Technology & Tools
- **Frontend**: HTML5, CSS3, and Vanilla JavaScript. We avoided heavy frameworks like React to ensure direct, uncompiled integration with the backend server and simplify the learning curve.
- **Backend Application**: PHP (via REST API methodology). PHP is universally supported on platforms like XAMPP and provides quick turnaround times.
- **Database**: MySQL, utilizing PDO (PHP Data Objects) for secure, prepared queries to prevent SQL injection.
- **Development Tools**: VS Code (IDE), XAMPP (Local Web/DB Server), and Postman (API debugging).
- **Version Control**: Git / GitHub for incremental change tracking.

## 4. Roles and Contributions
As a solo developer (or acting as a unified team entity), the responsibilities included full-stack engineering, from the initial MySQL schema design up to the responsive frontend UI and API integration.
