#Import Through CSV
Tired of creating content for different entities? Now you can create content for references entities through this module quickly and easily through a single csv file upload. If your content type refers to any other entity, the content for your content type and that entity will now be created automatically. You can now also create content for multivalued field and implement taxonomy hierarchy. 

#Installation
1. Create a folder named csv in your /default/files folder.
2. Download the module.
3. Install and enable it.
3. Go to /admin/content.
4. A new tab named Import Through CSV will be created under Contents.
5. Click the tab and Select the content type whose content is required to be created.
6. Create a CSV file, which will have its column titles, same as the machine name of the fields of that content type. For eg:- If you have a field with machine name field_author in content type (Book), the title of the column(containing author names) will also be field_author.

   a. The CSV must contain a title column to create title of the selected Content Type.

   b. If you have a multivalued field, you will have to fill all its value in the same cell of the CSV file and separate it with | symbol. For eg:- field_phone is a multivalued field. The column of field_phone in the CSV file will have the phone numbers in the same cell (1234567890|23456790|27898765) etc.

   c. If you want to implement the taxonomy hierarchy feature of Drupal, You will have to mention the parent of the taxonomy term in the CSV file under parent column. For eg:- Classics falls under genre Fiction which in turn falls under Story Book. So if you want to create the taxonomy term Classic, mention its parent Fiction(immediate parent) under parentcolumn of the CSV file. If Fiction is not created, it will be created first, and then Classic will be created with the flow.

  d. If you want to create Users. Mention their name under name column, mail under mail column etc of CSV file.

7. Upload the file and the contents will be created. If the selected content type refers to any other entity, the content of that entity will also be created at a flow, followed that you have given all the contents in that same CSV file.
