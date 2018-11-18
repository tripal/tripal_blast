Blast Target Databases
=======================

"Target Database" is the BLAST terminology for a database you want your users to be able to BLAST against. For example, on the NCBI Blast website they have a nucleotide and protein target database.

Add BLAST Database
-------------------

To add one to the "BLAST Databases" drop-down on the Blast program forms, in the "Navigation" menu go to "Add Content" > "Blast Database". Then fill out the form with the human readable name of your blast database (shown to the user in the drop-down) and the path to the blast database (passed to NCBI Blast).

.. image:: targetdbs.1.nodeform.png

For example, the above form will add "Tripalus Databasica Genome v1.0" to the "BLAST Databases" drop-down on the Nucleotide BLAST (blastn) form.

Linkouts
--------

These settings will be used to transform the hit name into a link to additional information. The linkout type determines how the URL will be formed:

 - **Generic Link**: Creates a generic link using a Tripal External Database and the backbone names from the blast database.
 - **GBrowse Link**: Creates a link to highlight blast results on an existing GBrowse. This requires the blast database consist of backbone sequences of the same name and version as the GBrowse instance.
 - **JBrowse Link**: Creates a link to highlight blast results on an existing JBrowse. This requires the blast database consist of backbone sequences of the same name and version as the JBrowse instance.
