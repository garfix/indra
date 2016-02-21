# Indra

## Introduction

This is a proof-of-concept library for the following idea:

> Is it possible to do version control on the data of a database, just as you can with source code?

The answer, it appears, is: Yes, you can!

This library, written in PHP 5.6, and using an existing relational database (e.g. MySql), has these features:

 * It generates database tables and PHP classes for types, attributes, and models
 * Each change to the database is done in a commit, in a branch.
 * The default branch is master. Branches can be created starting from the last commit of an existing branch. 
 * A branch may be merged into another branch.
 * An application can check out a previous commit of the data and view that data just like it was the last version of the data.

## Applications

Note! This library itself is just meant as a proof-of-concept. It works, but is not tested enough to be used in a production environment.
 
A more mature version of this system could be used for:
 
 * Auditing: keep track of the Who, What, When and Why of changes in the data
 * Recovering: undo unintended changes in the data
 * Debugging: to debug a problem it can be handy to use your application with the data as it was at some specific time in the past
 * Fiating: data branching allows your user to create changes in a non-live branch and merge it to the live branch after a review  
 
## How does it work?

The library uses these techniques: 

### Table and class generation

The developer writes a type definition. Database tables and PHP classes are generated from this definition.

### UUIDs

All table names, attribute names, and object ids are 22-byte alfanumeric UUIDs. Every identifier is a UUID.

### Commits and diffs

All changes to the database are grouped in commits. A commit contains the time of the commit, the name of the active user, a description of the change, 
and a series of diffs: short descriptions of the changes made to the database. Where a diff in a source code version control system describes lines of code being added or removed,
a diff here describes objects and attributes being added and removed.

### Branching: all tables are cloned, but in a lazy way 

When a new branch is created, the library creates a copy of each table. However, these are "shallow" copies. A table is not actually copied until the branch starts to make a change in it.
  This technique of shallow copying makes creating a branch a lightweight process. It may be compared to the way PHP creates copies of arrays, for example.

### Merging: replaying the diffs

When a source branch is merged into a target branch, the library searches the last common commit that both branches shared in the past.
 Then it replays all diffs from that common commit to the last commit in the source branch and accumulates them in a single commit in the target branch.
 The tables of the target branch are also updated with all diffs.
 
### Checkout: create temporary snapshots of tables that are actually needed

An activate application may choose to use any previous version of the data (any commit) to be used in stead of the latest version of the data.
 When such an application requests the use of a table, the library will create a snapshot of the table populated with the data as it was at the time of the requested commit.
 A clone is made of each table used, and all diffs from that commit until the latest commit in the branch are executed (replayed) on that snapshot.
 This means that only those snapshots are created that are actually needed on the requested page. This will usually just be a small subset of all tables in the database.
 A snapshot will be stored as cache until it is removed (say, once a day, by a scheduled process).

Any version control system providing checkout of previous versions will need to make a trade-off decision that involves both access time and data storage requirements. 
This library decided for reasonable access times for most applications, while saving on storage requirements. The use case this library has for checkouts is the one for debugging.
 At that time the user will be prepared to wait a few seconds for the snapshot, since the alternative is looking for a database backup.
 This use case, however, does not permit large investments in database size. 
  
### View history

A user may ask for a list of all changes to the database. He will then be shown who made the change, when it was made, a description of the change, and the actual attribute values involved.
 It is possible to filter this list by user, time, description, object id and object type.

### Undo: replay diffs in reverse
  
A user may request an "undo" of one or more commits. At that point the library will create a new commit, an "undo commit", that consists of a combination of the requested commits, 
in reversed order.
  
## Read more

More information about the use of the library and the structures used can be found in the [wiki](https://github.com/garfix/indra/wiki).