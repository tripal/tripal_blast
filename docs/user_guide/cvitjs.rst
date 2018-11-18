
Whole Genome BLAST Hit Visualization (CViTjs)
=============================================

1. `Download CViTjs <https://github.com/LegumeFederation/cvitjs>`_ and copy the code to your webserver. It needs to be placed in ``[your drupal root]/sites/all/libraries``. To download, execute the git command inside the ``libraries/`` directory:

.. code:: bash

  git clone https://github.com/LegumeFederation/cvitjs.git

2. CViTjs will have a config file in its root directory named ``cvit.conf``. This file provides information for whole genome visualization for each genome BLAST target. Make sure the config file can be edited by your web server.
3. Enable CViTjs from the BLAST module administration page.
4. Edit the configuration file to define each genome target. These will look like:

.. code:: yaml

  [data.Cajanus cajan - genome]
  conf = data/cajca/cajca.conf
  defaultData = data/cajca/cajca.gff

Where:

 - the section name, "data.Cajanus cajan - genome", consists of "data." followed by the name of the BLAST target node,
 - the file "cajca.conf" is a cvit configuration file which describes how to draw the chromosomes and BLAST hits on the Cajanus cajan genome,
 - and the file "cajca.gff" is a GFF3 file that describes the Cajanus cajan chromosomes.

At the top of the configuration file there must be a [general] section that defines the default data set. For example:

.. code:: yaml

    [general]
    data_default = data.Cajanus cajan - genome

5. Edit the nodes for each genome target (nodes of type "BLAST Database") and enable whole genome visualization. Remember that the names listed in the CViTjs config file must match the BLAST node name. In the example above, the BLAST database node for the Cajanus cajan genome assembly is named "Cajanus cajan - genome"

Notes
------

- The .conf file for each genome can be modified to suit your needs and tastes. See the sample configuration file, data/test1/test1.conf, and the `CViTjs documentation <https://github.com/LegumeFederation/cvitjs#using-cvitjs>`_.
- Each blast target CViTjs configuration file must define how to visualize blast hits or you will not see them.

.. code:: yaml

  [blast]
  feature = BLASTRESULT:match_part
  glyph   = position
  shape = rect
  color   = #FF00FF
  width = 5


- You will have to put the target-specific conf and gff files (e.g. ``cajca.conf`` and ``cjca.gff``) on your web server, in the directory, ``sites/all/libraries/cvitjs/data``. You may choose to group files for each genome into subdirectories, for example, ``sites/all/libraries/cvitjs/data/cajca``.
- It is important to make sure that ``cvit.conf`` points to the correct data directory and the correct ``.gff`` and ``.conf`` files for the genome in question. For more information about how to create the ``.gff`` file, see the `documentation <https://github.com/LegumeFederation/cvitjs#how-to>`_.
