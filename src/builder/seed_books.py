
import mysql.connector
import os

DB_HOST = os.getenv("DB_HOST", "db")
DB_NAME = os.getenv("DB_NAME", "myapp")
DB_USER = os.getenv("DB_USER", "user")
DB_PASS = os.getenv("DB_PASS", "password")

kn_king_chapters = [
    "1. Introducing C", "2. C Fundamentals", "3. Formatted Input/Output", "4. Expressions",
    "5. Selection Statements", "6. Loops", "7. Basic Types", "8. Arrays", "9. Functions",
    "10. Program Organization", "11. Pointers", "12. Pointers and Arrays", "13. Strings",
    "14. The Preprocessor", "15. Writing Large Programs", "16. Structures, Unions, and Enumerations",
    "17. Advanced Uses of Pointers", "18. Declarations", "19. Program Design", "20. Low-Level Programming",
    "21. The Standard Library", "22. Input/Output", "23. Library Support for Numbers and Character Data",
    "24. Error Handling", "25. Internationalization", "26. Miscellaneous Library Functions", "27. Additional C99 Support"
]

csapp_chapters = [
    "1. A Tour of Computer Systems", "2. Representing and Manipulating Information", "3. Machine-Level Representation of Programs",
    "4. Processor Architecture", "5. Optimizing Program Performance", "6. The Memory Hierarchy", "7. Linking",
    "8. Exceptional Control Flow", "9. Virtual Memory", "10. System-Level I/O", "11. Network Programming",
    "12. Concurrent Programming"
]

def run_seed():
    conn = mysql.connector.connect(host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME)
    cursor = conn.cursor()

    # 1. Find KN King Goal
    cursor.execute("SELECT id FROM vision_goals WHERE title LIKE '%K.N King%' OR title LIKE '%Modern Approach%' LIMIT 1")
    res = cursor.fetchone()
    if res:
        goal_id = res[0]
        print(f"Found KN King Goal ID: {goal_id}")
        cursor.execute(f"DELETE FROM vision_milestones WHERE goal_id = {goal_id}")
        for ch in kn_king_chapters:
            cursor.execute("INSERT INTO vision_milestones (goal_id, title) VALUES (%s, %s)", (goal_id, ch))
    else:
        print("Could not find 'C Programming a Modern Approach' goal.")

    # 2. Find CS:APP Goal
    cursor.execute("SELECT id FROM vision_goals WHERE title LIKE '%Computer Systems%' OR title LIKE '%CS:APP%' LIMIT 1")
    res = cursor.fetchone()
    if res:
        goal_id = res[0]
        print(f"Found CS:APP Goal ID: {goal_id}")
        cursor.execute(f"DELETE FROM vision_milestones WHERE goal_id = {goal_id}")
        for ch in csapp_chapters:
            cursor.execute("INSERT INTO vision_milestones (goal_id, title) VALUES (%s, %s)", (goal_id, ch))
    else:
        print("Could not find 'Computer Systems (CS:APP)' goal.")

    conn.commit()
    cursor.close()
    conn.close()

if __name__ == "__main__":
    run_seed()
