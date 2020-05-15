# ProjectLinker
This module links a member project to other projects via a parent project.  The em must be configured with the pid of the parent project as well as the field names of the record_id and mrn.  

The member project must be registered in the parent project prior to enabling this em.  The parent project requres 
the following fields for the member project:
<ul>
<li>redcap_pid: The pid of the member project</li>
<li>project_name: The name of the member project</li>
<li>recordid_field: The record_id of the member project</li>
<li>mrn_field: The mrn field of the member project</li>
<li>access: Currently can only be set globally as data dictionary only, mrns, or all data.  The access will be 
refined in future versions.</li>
</ul>

Once this em is enabled for a redcap project, it searches other linked projects for intersecting mrns, and display 
this data as configured by the access variable.

