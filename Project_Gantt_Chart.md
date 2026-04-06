# SplitEase: Project Gantt Chart

The following is a representation of our 4-week Agile schedule broken into Sprints.

*(Note: Ensure you export your Markdown using a markdown-to-pdf plugin, or paste the snippet into [Mermaid Live Editor](https://mermaid.live) to render the exact chart)*

```mermaid
gantt
    title SplitEase Development Schedule
    dateFormat  YYYY-MM-DD
    
    section Sprint 0 (Design Phase)
    Planning & Requirements       :done,    des1, 2026-03-01, 2026-03-07
    System Design & UI Mockups    :done,    des2, 2026-03-08, 2026-03-14
    
    section Sprint 1 (Backend API)
    Database Schema Setup         :active,  backend1, 2026-03-15, 3d
    PHP API Endpoints & Ledger    :active,  backend2, after backend1, 4d
    
    section Sprint 2 (Frontend)
    HTML Templates & CSS Styling  :         frontend1, 2026-03-22, 5d
    Vanilla JS & Integration      :         frontend2, after frontend1, 2d
    Final Polish & Documentation  :         docs, after frontend2, 1d
```
