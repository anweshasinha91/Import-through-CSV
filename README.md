# Import-through-CSV
Now you can create content for references entities through this module quickly and easily. If your content type refers to any other entity, the content for your content type and that entity will now be created automatically.

# Installation

1. Create a folder named csv in your /default/files folder.
2. Download the module.
3. Install and enable it.
3. Go to /admin/config.
4. Under DEVELOPMENT group, go to Import Through CSV.
5. Select the content type whose content is required to be created.
6. Create a CSV file, which will have its column titles, same as the name of the fields of that content type. For eg:- If you have a field with machine name field_author in content type (Book), the title of the column(containing author names) will also be field_author.
7. Upload the file and the contents will be created. If the selected content type refers to any other entity, the content of that entity will also be created at a flow, followed that you have given all the contents in that same csv file.
