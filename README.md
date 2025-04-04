
# 📦 Property Listing API

## 🚀 Getting Started

### 1. Clone the Repository
```bash
git clone https://github.com/VortexWeb-Dev/property-listing-backend.git
cd your-repo-name
```

### 2. Install Dependencies
```bash
npm install
composer install
```

---

## 🌿 Branching Strategy

We follow a clear and structured Git branching strategy:

| Branch Name        | Purpose                                   |
|--------------------|-------------------------------------------|
| `main`             | Production-ready code only                |
| `dev`              | Development integration branch            |
| `feature/*`        | New features                              |
| `bugfix/*`         | Bug fixes                                 |
| `hotfix/*`         | Urgent fixes directly affecting production|
| `release/*`        | Release preparation branches              |

> ✅ Always branch from `dev` for features and bugfixes.

### Example Branch Names
- `feature/user-authentication`
- `bugfix/fix-login-error`
- `hotfix/crash-on-startup`
- `release/v1.2.0`

---

## 🧑‍💻 Contributing Workflow

### Step-by-Step: How to Contribute via Pull Requests

1. **Create a Branch**
   ```bash
   git checkout dev
   git pull origin dev
   git checkout -b feature/your-branch-name
   ```

2. **Make Your Changes**

3. **Commit Properly**
   ```bash
   git add .
   git commit -m "feat: added login functionality"
   ```

   **Commit Message Format:**
   ```
   <type>: <short description>
   ```
   **Common types:** `feat`, `fix`, `docs`, `refactor`, `test`, `chore`

4. **Push Your Branch**
   ```bash
   git push origin feature/your-branch-name
   ```

5. **Create a Pull Request**
   - Go to the repository on GitHub
   - Open a new PR targeting the `dev` branch
   - Add a meaningful **title and description**
   - Link any relevant **issues**
   - Request **reviewers** (if applicable)

6. **Wait for Review & Approval**
   - Make any requested changes
   - Once approved, the branch will be merged by a maintainer

7. **Clean Up (Optional)**
   ```bash
   git branch -d feature/your-branch-name
   git push origin --delete feature/your-branch-name
   ```

---

## ✅ Code Quality Standards

- Follow existing code style and structure
- Write meaningful commit messages
- Test your changes thoroughly
- Use environment files properly (`.env`, `.env.example`)
- Add documentation/comments where needed

---

## 📄 Environment Setup

Create a `.env` file based on `.env.example` and fill in required fields.

---

## 🙋‍♂️ Need Help?

Feel free to open an issue or start a discussion!