
# Managing BLAST databases

!!! warning "Under Development"

    This documentation is still being developed as this module is being upgraded.

## How do I format my blast databases for use with Tripal BLAST?

NCBI Blast+ comes with a program names `makeblastdb` which we will use to format a fasta file into a blast database.

Arguements for makeblastdb command:

- `-dbtype` indicates the type of sequence in the fasta file. It can be either `nucl` or `prot`
- `-parse_seqids` indicates that you want to parse the fasta headers to be used by the blast query program
- `-hash_index` creates an index for faster blast searches
- `-in FASTAFILE` indicates that the fasta file you replace the token FASTAFILE with should be the input (i.e. the source for the blast database). Note: gzipped fasta is not a supported type for your input file.
- `-input_type` indicates the type of file you provided to `-in` and it can be either `fasta` or `blastdb`.
- `-title` should be a human-readable title describing this blast database. I recommend using the full name of the assembly including the version and the accession if known.
- `-out GENOMEASSEMBLY` will be the filename of the blast database produced not including any file type suffix. The token GENOMEASSEMBLY should be replaced with an alphanumeric string uniquely describing the genome assembly that is the source for the blast database.

### Nucleotide

The command template to create a blast database from a nucleotide fasta file is:

```
makeblastdb -dbtype nucl -parse_seqids -hash_index -in FASTAFILE -input_type fasta -title "Genome Assembly Title with version" -out GENOMEASSEMBLY
```

An example which is actually how the demonstration blast database distributed with this module was created is:

```
makeblastdb -dbtype nucl -parse_seqids -hash_index -in Creinhardtii_281_v5.0.fa -input_type fasta -title "Chlamydomonas reinhardtii v5.6" -out Chlamydomonas_reinhardtii_v5.6
```

and the output of the command was

> Building a new DB, current time: 03/31/2024 22:10:54
> New DB name:   Chlamydomonas_reinhardtii_v5.6
> New DB title:  Chlamydomonas reinhardtii v5.6
> Sequence type: Nucleotide
> Keep Linkouts: T
> Keep MBits: T
> Maximum file size: 1000000000B
> Adding sequences from FASTA; added 54 sequences in 1.82259 seconds.

## Protein

The command template to create a blast database from a protein fasta file is:

```
makeblastdb -dbtype prot -parse_seqids -hash_index -in FASTAFILE -input_type fasta -title "Genome Assembly Title with version" -out GENOMEASSEMBLY
```

An example which is actually how the demonstration blast database distributed with this module was created is:

```
makeblastdb -dbtype nucl -parse_seqids -hash_index -in Creinhardtii_281_v5.5.protein.fa -input_type fasta -title "Chlamydomonas reinhardtii v5.6 Protein" -out Chlamydomonas_reinhardtii_v5.6_protein
```

and the output of the command was

> Building a new DB, current time: 03/31/2024 22:38:57
> New DB name:   Chlamydomonas_reinhardtii_v5.6_protein
> New DB title:  Chlamydomonas reinhardtii v5.6 Protein
> Sequence type: Nucleotide
> Keep Linkouts: T
> Keep MBits: T
> Maximum file size: 1000000000B
> Adding sequences from FASTA; added 19526 sequences in 4.40205 seconds.
