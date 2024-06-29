This project is a custom system for the College of Science at Al-Nahrain University to organize activities for lecturers in departments.

The proposed project aims to build tools needed for fulfilling inquiries from colleges, universities, or any other party about activities. The tools should generate the reports needed dynamically as possible. 
Additionally, the project will focus on enhancing the reporting capabilities of these tools to provide customizable reports that offer valuable insights into departmental operations. The tools will be designed to be user-friendly and easily adaptable to meet the diverse needs of lecturers, ultimately improving the efficiency and effectiveness of departmental administration in academia.

Login page / which is the first page in it all the users (admin & lecturer) must log in to the system, but only through their official university account , (using OAuth 2.0 Client IDs // google cloud API )
![image](https://github.com/FatemaMajid/supportingTools/assets/143040754/27806d73-b12c-4366-9fdb-4063c69e0452)

Home page / activities display interface : the home page is the interface for displaying the complete activities in the form of a table that displays the name of the activity, the name of the lecturer, the date and the name of the activity, in addition to the option (عرض التفاصيل), which allows the possibility of displaying the full information about the activity , and the delete option , and the ability to search (filter) on the activities. In addition to the possibility of exporting activity information in the form of an Excel report (تصدير جميع المعلومات/بحسب البحث).
The main interface is the same for all users (admin or lecturer). The only difference is that the admin can see his/her own activities in addition to the activities of all lecturers, and the lecturer can only see his/her own activities.
![image](https://github.com/FatemaMajid/supportingTools/assets/143040754/6dcefe2e-62aa-4947-9846-fe39dc9b82b5)

View details & edit / in the activity display table, there is an option (عرض التفاصيل). When you click on it, a small window opens containing a form of complete activity information with the ability to modify it. 
![image](https://github.com/FatemaMajid/supportingTools/assets/143040754/3000c787-d24d-4b38-b005-6bad81e6b12b)

Insert page / The system includes a group of activities that require information that varies from one type to another, which may require different input interfaces for each type.
The input interface in this system is a single form in which the input fields change dynamically according to the type of activity to be entered.
For the admin, he/she can enter information about his/her own activities in addition to the activities of all lecturers by selecting the lecturer from the list of lecturers’ names.
For the lecturer, the process of entering activities is the same as for the admin, but the lecturer can only enter his own activities, as the lecturer’s name is fixed in the (اسم التدريسي) field and cannot be changed.
![image](https://github.com/FatemaMajid/supportingTools/assets/143040754/f7b8be33-7e4a-4635-a153-0eb53210981a)

During the process of entering the activity, if the activity already exists, it appears as a suggestion to the user depends on the activity date or the activity name and he/she can choose it directly without the need to enter its information again.
![image](https://github.com/FatemaMajid/supportingTools/assets/143040754/7a1f285d-5291-4e43-ae9a-830d0ab6afb7)

Generating Reports / it is the main function of the project , The system provides the ability to create Excel reports for activities, either a report for all activities or a report according to search results.
Each report is divided into a number of sheets (a sheet for each type of activity and a sheet for all information) , this done using PhpSpreadsheet library
![image](https://github.com/FatemaMajid/supportingTools/assets/143040754/ef3d8a56-72cd-433e-bce4-ce7f6757c5f9)








